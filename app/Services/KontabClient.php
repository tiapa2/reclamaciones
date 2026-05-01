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
 *   POST /invoices/sales           → crea factura + posta + envía a DGII
 *   GET  /lookups/health           → smoke test para "Probar conexión"
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
        $res = $this->request('GET', '/lookups/health');

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

        $payload = [
            'contact_id' => $kontabContactId,
            'date' => $invoice->invoice_date->toDateString(),
            'income_type' => '02', // Servicios médicos
            'auto_post' => true,
            'auto_send_dgii' => true,
            'reference' => "Factura {$invoice->id}",
            'items' => $invoice->items->map(fn ($it) => [
                'description' => $it->description
                    ?? ($it->patient_name ? "Servicio médico — {$it->patient_name}" : 'Servicio médico'),
                'quantity' => 1,
                'unit_price' => (float) $it->amount,
                'tax_rate' => 0, // ARS = exento; ajustar si alguna cae bajo ITBIS
            ])->all(),
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
     *   X-Api-Signature — hex(HMAC-SHA256(secret, ts + "\n" + METHOD + "\n" + uri + "\n" + sha256(body))))
     */
    private function request(string $method, string $path, array $body = [])
    {
        $url = $this->baseUrl.$path;
        $rawBody = $body ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $ts = (string) time();
        $bodyHash = hash('sha256', $rawBody);
        $stringToSign = "{$ts}\n".strtoupper($method)."\n{$path}\n{$bodyHash}";
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
