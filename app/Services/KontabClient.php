<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Insurer;
use App\Models\InsurerKontabContact;
use App\Models\Invoice;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente HTTP firmado HMAC para hablar con la API de integraciones de kontab-erp.
 * Cada doctor tiene su propia api_key + secret guardados encriptados; este client
 * los desencripta on-demand.
 *
 * Endpoints consumidos:
 *   POST /contacts                 → upsert ARS como contacto (cacheado vía pivot)
 *   GET  /lookups/ncf-types        → resolver el ncf_type_id por código DGII
 *   POST /invoices/sales           → crea factura + posta + envía a DGII
 *   GET  /ping                     → smoke test para "Probar conexión"
 *
 * Scopes requeridos en la credencial: contacts:write, lookups:read,
 * invoices.sales:write y dgii:send.
 */
class KontabClient
{
    private string $baseUrl;

    private string $apiKeyId;

    private string $apiSecret;

    public function __construct(private Doctor $doctor)
    {
        if (! $doctor->kontab_api_key_id || ! $doctor->kontab_api_secret_encrypted) {
            throw new RuntimeException('El doctor no tiene credenciales de kontab-erp configuradas.');
        }

        $this->baseUrl = rtrim(config('services.kontab.base_url', 'https://kontab.com.do/api/integration/v1'), '/');
        $this->apiKeyId = $doctor->kontab_api_key_id;
        $this->apiSecret = Crypt::decryptString($doctor->kontab_api_secret_encrypted);
    }

    /** Validación rápida de credenciales — usado por el botón "Probar conexión". */
    public function testConnection(): bool
    {
        // /ping es el smoke-test autenticado de kontab-erp (no requiere scope):
        // confirma que la firma HMAC, la credencial y la conexión funcionan.
        $res = $this->request('GET', '/ping');

        return $res->successful();
    }

    /**
     * Devuelve el kontab_contact_id para esta ARS, creando el contacto en kontab-erp
     * si no existe en cache local. Cache key: (doctor_id, insurer_id).
     */
    public function upsertContactForInsurer(Insurer $insurer): int
    {
        $cached = InsurerKontabContact::where('doctor_id', $this->doctor->id)
            ->where('insurer_id', $insurer->id)
            ->first();

        if ($cached) {
            return $cached->kontab_contact_id;
        }

        $payload = [
            'type' => 'customer',
            'name' => $insurer->name,
            'rnc_cedula' => $insurer->rnc,
            'email' => $insurer->email,
            'phone' => $insurer->phone,
        ];

        $res = $this->request('POST', '/contacts', $payload);
        $this->throwIfFailed($res, 'upsert contacto');

        $contactId = (int) ($res->json('id') ?? $res->json('data.id'));

        InsurerKontabContact::create([
            'doctor_id' => $this->doctor->id,
            'insurer_id' => $insurer->id,
            'kontab_contact_id' => $contactId,
        ]);

        return $contactId;
    }

    /**
     * Crea la factura de venta en kontab-erp (postea + envía a DGII).
     * Retorna la respuesta cruda con: id, ncf, track_id, security_code, dgii_status, etc.
     */
    public function createSalesInvoice(Invoice $invoice, int $kontabContactId): array
    {
        $invoice->loadMissing(['items', 'ncfType']);

        // kontab-erp exige un tipo de NCF para poder contabilizar y emitir el e-CF.
        // Resolvemos el id del tipo en kontab-erp a partir del código DGII (E31, E32…)
        // que ya trae la factura local; ambos sistemas usan los códigos estándar.
        $ncfCode = $this->toElectronicCode((string) $invoice->ncfType?->prefix);
        if (! $ncfCode) {
            throw new RuntimeException('La factura no tiene un tipo de NCF configurado para facturación electrónica.');
        }
        $kontabNcfTypeId = $this->resolveNcfTypeId($ncfCode);
        if (! $kontabNcfTypeId) {
            throw new RuntimeException("kontab-erp no tiene configurado el tipo de NCF «{$ncfCode}» para esta empresa.");
        }

        // Retención de ISR (honorarios). Kontab-erp la recibe a nivel encabezado
        // (withholding_isr) y la prorratea por línea al emitir el e-CF. Cuando hay
        // retención, cada línea debe marcar el indicador de agente de retención.
        $isrRetention = (float) ($invoice->isr_retention_amount ?? 0);
        $hasRetention = $isrRetention > 0;

        $payload = [
            'contact_id' => $kontabContactId,
            'ncf_type_id' => $kontabNcfTypeId,
            'date' => $invoice->invoice_date->toDateString(),
            'income_type' => '02', // Servicios médicos
            'auto_post' => true,
            'auto_send_dgii' => true,
            'reference' => "Factura {$invoice->id}",
            'withholding_isr' => $isrRetention,
            'items' => $invoice->items->map(fn ($it) => array_filter([
                'description' => $it->description
                    ?? ($it->patient_name ? "Servicio médico — {$it->patient_name}" : 'Servicio médico'),
                'quantity' => 1,
                'unit_price' => (float) $it->amount,
                'tax_rate' => 0, // ARS = exento; ajustar si alguna cae bajo ITBIS
                'retention_agent_indicator' => $hasRetention ? 1 : null,
            ], fn ($v) => $v !== null))->all(),
            'notes' => $this->buildInvoiceNotes($invoice),
        ];

        $res = $this->request('POST', '/invoices/sales', $payload);
        $this->throwIfFailed($res, 'crear factura');

        $data = $res->json('data') ?? $res->json();

        return [
            'id' => (int) ($data['id'] ?? 0),
            'ncf' => $data['ncf'] ?? null,
            'track_id' => $data['track_id'] ?? null,
            'security_code' => $data['security_code'] ?? null,
            'dgii_status' => strtolower($data['dgii_status'] ?? 'pending'),
            'raw' => $data,
        ];
    }

    /**
     * Traduce un código de NCF local (papel, B0x/B1x) a su equivalente e-CF de DGII.
     * Para facturación electrónica el comprobante SIEMPRE es electrónico; si la
     * factura trae un tipo local (facturas antiguas o formulario viejo), lo mapeamos.
     * Si ya es un código E-*, se devuelve tal cual.
     */
    private function toElectronicCode(string $code): string
    {
        $code = strtoupper(trim($code));
        if ($code === '' || str_starts_with($code, 'E')) {
            return $code;
        }

        return [
            'B01' => 'E31', // Crédito Fiscal
            'B02' => 'E32', // Consumo
            'B14' => 'E44', // Régimen Especial
            'B15' => 'E45', // Gubernamental
            'B16' => 'E46', // Exportaciones
        ][$code] ?? $code;
    }

    /**
     * Resuelve el id del tipo de NCF en kontab-erp a partir de su código DGII
     * (E31, E32, E44…). Cachea el catálogo por instancia para no repetir la llamada.
     *
     * @var array<string,int>|null
     */
    private ?array $ncfTypeCache = null;

    /**
     * Estado + representación fiscal del e-CF en kontab-erp (para el PDF):
     * ncf, status, security_code, qr_url, qr_svg_base64, fecha_firma.
     *
     * @return array{ncf:?string,status:?string,security_code:?string,qr_url:?string,qr_svg_base64:?string,fecha_firma:?string}
     */
    public function getDgiiStatus(int $kontabInvoiceId): array
    {
        $res = $this->request('GET', "/invoices/sales/{$kontabInvoiceId}/dgii-status");
        $this->throwIfFailed($res, 'consultar estado DGII');

        $d = $res->json('data') ?? $res->json() ?? [];

        return [
            'ncf' => $d['ncf'] ?? null,
            'status' => $d['status'] ?? null,
            'security_code' => $d['security_code'] ?? null,
            'qr_url' => $d['qr_url'] ?? null,
            'qr_svg_base64' => $d['qr_svg_base64'] ?? null,
            'fecha_firma' => $d['fecha_firma'] ?? null,
            'valid_until' => $d['valid_until'] ?? null,
        ];
    }

    /**
     * Tipos de comprobante ELECTRÓNICO (e-CF) que la empresa tiene disponibles en
     * kontab-erp: solo secuencias activas, con números restantes y código E-* .
     * Se usa para poblar el selector de "Tipo NCF" cuando el médico es electrónico.
     *
     * @return array<int,array{code:string,name:string,remaining:int,next_ncf:string}>
     */
    public function availableEinvoiceTypes(): array
    {
        $res = $this->request('GET', '/lookups/ncf-availability');
        $this->throwIfFailed($res, 'listar disponibilidad e-NCF');

        $rows = $res->json('data') ?? $res->json() ?? [];

        return collect($rows)
            ->filter(fn ($r) => ($r['is_active'] ?? false)
                && (int) ($r['remaining'] ?? 0) > 0
                && str_starts_with(strtoupper((string) ($r['ncf_code'] ?? '')), 'E'))
            ->map(fn ($r) => [
                'code' => strtoupper($r['ncf_code']),
                'name' => $r['ncf_name'] ?? $r['ncf_code'],
                'remaining' => (int) $r['remaining'],
                // e-NCF = prefijo (E31) + 10 dígitos de secuencia.
                'next_ncf' => strtoupper($r['ncf_code']).str_pad((string) ($r['current_number'] ?? 0), 10, '0', STR_PAD_LEFT),
            ])
            ->values()
            ->all();
    }

    private function resolveNcfTypeId(string $code): ?int
    {
        if ($this->ncfTypeCache === null) {
            $res = $this->request('GET', '/lookups/ncf-types');
            $this->throwIfFailed($res, 'listar tipos de NCF');

            $rows = $res->json('data') ?? $res->json() ?? [];
            $this->ncfTypeCache = [];
            foreach ($rows as $row) {
                if (isset($row['code'], $row['id'])) {
                    $this->ncfTypeCache[strtoupper($row['code'])] = (int) $row['id'];
                }
            }
        }

        return $this->ncfTypeCache[strtoupper($code)] ?? null;
    }

    private function buildInvoiceNotes(Invoice $invoice): string
    {
        $parts = ["Factura emitida desde Kontab Reclamaciones (#{$invoice->id})"];
        if ($invoice->insurer) {
            $parts[] = "ARS: {$invoice->insurer->name}";
        }
        if ($invoice->doctor) {
            $parts[] = "Médico: {$invoice->doctor->full_name}";
        }

        return implode(' · ', $parts);
    }

    /**
     * Petición HTTP firmada HMAC. Headers:
     *   X-Api-Key       — id de la credencial
     *   X-Api-Timestamp — unix seconds
     *   X-Api-Signature — hex(HMAC-SHA256(secret, ts + "\n" + METHOD + "\n" + REQUEST_URI + "\n" + sha256(body))))
     *
     * REQUEST_URI debe ser el path COMPLETO tal como lo recibe kontab-erp
     * (incluye el prefijo /api/integration/v1 y el query string), NO el path
     * relativo. kontab-erp valida contra $request->getRequestUri(); si firmamos
     * solo el path relativo la firma no coincide y responde 401 invalid_signature.
     */
    private function request(string $method, string $path, array $body = [])
    {
        $url = $this->baseUrl.$path;
        $rawBody = $body ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $ts = (string) time();
        $bodyHash = hash('sha256', $rawBody);

        $urlParts = parse_url($url);
        $requestUri = ($urlParts['path'] ?? $path)
            .(isset($urlParts['query']) ? '?'.$urlParts['query'] : '');

        $stringToSign = "{$ts}\n".strtoupper($method)."\n{$requestUri}\n{$bodyHash}";
        $signature = hash_hmac('sha256', $stringToSign, $this->apiSecret);

        return Http::withHeaders([
            'X-Api-Key' => $this->apiKeyId,
            'X-Api-Timestamp' => $ts,
            'X-Api-Signature' => $signature,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withBody($rawBody, 'application/json')
            ->timeout(20)
            ->send($method, $url);
    }

    private function throwIfFailed($response, string $action): void
    {
        if (! $response->successful()) {
            $msg = $response->json('message') ?? $response->body();
            throw new RuntimeException("kontab-erp ({$action}) HTTP {$response->status()}: {$msg}");
        }
    }
}
