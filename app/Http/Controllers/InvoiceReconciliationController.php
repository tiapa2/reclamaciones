<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceReconciliation;
use App\Models\InvoiceReconciliationItem;
use App\Models\InvoiceReconciliationRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceReconciliationController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $request->validate([
            'started_at' => ['nullable', 'date'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $invoice->load(['items', 'doctor', 'insurer']);

        // (Opcional) evitar si ya hay una abierta
        $hasOpen = $invoice->reconciliations()->where('status', 'open')->exists();
        if ($hasOpen) {
            return back()->withErrors(['reconciliation' => 'Ya existe una conciliación abierta para esta factura.']);
        }

        DB::transaction(function () use ($invoice, $request) {
            $rec = InvoiceReconciliation::create([
                'invoice_id' => $invoice->id,
                'doctor_id' => $invoice->doctor_id,
                'insurer_id' => $invoice->insurer_id,
                'status' => 'open',
                'started_at' => $request->input('started_at', now()->toDateString()),
                'reference_no' => $request->input('reference_no'),
                'notes' => $request->input('notes'),
                'created_by' => auth()->id(),
            ]);

            $rows = $invoice->items->map(function ($it) use ($rec) {
                $billed = (float) $it->amount;

                return [
                    'invoice_reconciliation_id' => $rec->id,
                    'invoice_item_id' => $it->id,

                    'service_date' => $it->service_date,
                    'patient_name' => $it->patient_name,
                    'affiliate_no' => $it->affiliate_no,
                    'authorization_no' => $it->authorization_no,

                    'amount_billed' => $billed,
                    'amount_paid' => 0,
                    'adjustment_amount' => 0,
                    'balance' => $billed,
                    'status' => 'pending',

                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            InvoiceReconciliationItem::insert($rows);
        });

        return back()->with('success', 'Conciliación iniciada correctamente.');
    }

    public function close(Request $request, InvoiceReconciliation $reconciliation)
    {
        if ($reconciliation->status !== 'open') {
            return back()->withErrors(['reconciliation' => 'Esta conciliación no está abierta.']);
        }

        $reconciliation->status = 'closed';
        $reconciliation->closed_at = now()->toDateString();
        $reconciliation->save();

        return back()->with('success', 'Conciliación cerrada.');
    }

    private function norm(?string $s): string
    {
        $s = strtoupper(trim((string)$s));
        $s = preg_replace('/\s+/', '', $s);
        return $s;
    }

    public function confirm(Request $request, InvoiceReconciliation $reconciliation)
    {
        if ($reconciliation->status !== 'review') {
            return back()->withErrors(['reconciliation' => 'Esta conciliación no está en revisión.']);
        }

        // seguridad: que solo haya 1 open
        $hasOpen = InvoiceReconciliation::where('invoice_id', $reconciliation->invoice_id)
            ->where('status', 'open')->exists();
        if ($hasOpen) {
            return back()->withErrors(['reconciliation' => 'Ya existe una conciliación abierta para esta factura.']);
        }

        $reconciliation->status = 'open';
        $reconciliation->save();

        // opcional: marcar run confirmado
        $run = InvoiceReconciliationRun::where('invoice_reconciliation_id', $reconciliation->id)
            ->orderByDesc('id')->first();
        if ($run && $run->status === 'prefilled') {
            $run->status = 'confirmed';
            $run->save();
        }

        return back()->with('success', 'Conciliación confirmada. Ya puedes ajustar y luego cerrar.');
    }

    public function autoUpload(Request $request, Invoice $invoice)
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20MB
            'started_at' => ['nullable', 'date'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $invoice->load(['items', 'doctor', 'insurer']);

        // No permitir si ya existe una OPEN
        $hasOpen = $invoice->reconciliations()->where('status', 'open')->exists();
        if ($hasOpen) {
            return back()->withErrors(['reconciliation' => 'Ya existe una conciliación abierta para esta factura.']);
        }

        // Guardar PDF
        $path = $request->file('pdf')->store('reconciliations', 'public');

        $recId = null;
        $runId = null;

        DB::transaction(function () use ($invoice, $request, $path, &$recId, &$runId) {

            // 1) Crear conciliación en REVIEW (pendiente confirmación)
            $rec = InvoiceReconciliation::create([
                'invoice_id'   => $invoice->id,
                'doctor_id'    => $invoice->doctor_id,
                'insurer_id'   => $invoice->insurer_id,
                'status'       => 'review', // 👈 estado review
                'started_at'   => $request->input('started_at', now()->toDateString()),
                'reference_no' => $request->input('reference_no'),
                'notes'        => $request->input('notes'),
                'type'         => 'auto',
                'source_file_path' => $path,
                'source_meta'  => null,
                'created_by'   => auth()->id(),
            ]);

            // 2) Duplicar items (como manual)
            $rows = $invoice->items->map(function ($it) use ($rec) {
                $billed = (float) $it->amount;

                return [
                    'invoice_reconciliation_id' => $rec->id,
                    'invoice_item_id' => $it->id,

                    'service_date' => $it->service_date,
                    'patient_name' => $it->patient_name,
                    'affiliate_no' => $it->affiliate_no,
                    'authorization_no' => $it->authorization_no,

                    'amount_billed' => $billed,
                    'amount_paid' => 0,
                    'adjustment_amount' => 0,
                    'balance' => $billed,
                    'status' => 'pending',

                    'match_confidence' => null,
                    'raw' => null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            InvoiceReconciliationItem::insert($rows);

            // 3) Crear RUN (auditoría)
            $run = InvoiceReconciliationRun::create([
                'invoice_reconciliation_id' => $rec->id,
                'mode' => 'automatic',
                'status' => 'uploaded',
                'source_file' => $path,
                'ai_result' => null,
                'matched_count' => 0,
                'error' => null,
                'created_by' => auth()->id(),
            ]);

            $recId = $rec->id;
            $runId = $run->id;
        });

        // 4) Llamar OpenAI fuera de la transacción
        $rec = InvoiceReconciliation::with('items')->findOrFail($recId);

        try {
            $result = $this->extractPdfWithOpenAI($invoice, $path);
            // Guardar raw ai_result de una vez (aunque falle la validación)
            if ($runId) {
                InvoiceReconciliationRun::whereKey($runId)->update([
                    'ai_result' => $result,
                ]);
            }

            // Validación: belongs_to_doctor
            if (empty($result['belongs_to_doctor'])) {
                if ($runId) {
                    InvoiceReconciliationRun::whereKey($runId)->update([
                        'status' => 'failed',
                        'error' => 'PDF no pertenece al doctor/aseguradora esperada.',
                    ]);
                }

                return back()->withErrors([
                    'reconciliation' => 'El PDF no parece pertenecer al doctor/aseguradora de esta factura.',
                ]);
            }

            // 5) Prefill por authorization_no (normalizado)
            $aiLines = $result['lines'] ?? [];
            if (!is_array($aiLines)) $aiLines = [];

            // indexa items de conciliación por authorization_no normalizado (clave completa)
            $itemsByAuth = $rec->items->mapWithKeys(function ($it) {
                return [$this->normAuth($it->authorization_no) => $it];
            });

            // índice secundario por últimos 5 dígitos del authorization_no.
            // Solo se usa como fallback cuando el PDF trae la autorización con prefijos
            // (p.ej. "2025-34-0045923" vs "45923"). Si hay colisión de sufijo entre
            // dos items distintos, se descarta la clave para evitar matches ambiguos.
            $suffixIndex = [];
            foreach ($rec->items as $itAll) {
                $full = $this->normAuth($itAll->authorization_no);
                $suf = $this->authSuffix($itAll->authorization_no);
                if ($suf === '') continue;
                if (array_key_exists($suf, $suffixIndex)) {
                    // colisión: si apunta a otro item, marcamos como ambigua (null)
                    if ($suffixIndex[$suf] !== null && $this->normAuth($suffixIndex[$suf]->authorization_no) !== $full) {
                        $suffixIndex[$suf] = null;
                    }
                } else {
                    $suffixIndex[$suf] = $itAll;
                }
            }

            $matched = 0;
            $unmatched = [];

            foreach ($aiLines as $line) {
                $authRaw = $line['authorization_no'] ?? null;
                $key = $this->normAuth($authRaw);

                $it = null;
                if ($key !== '' && isset($itemsByAuth[$key])) {
                    $it = $itemsByAuth[$key];
                } else {
                    // fallback: comparar por últimos 5 dígitos
                    $suf = $this->authSuffix($authRaw);
                    if ($suf !== '' && isset($suffixIndex[$suf]) && $suffixIndex[$suf] !== null) {
                        $it = $suffixIndex[$suf];
                    }
                }

                if ($it === null) {
                    $unmatched[] = [
                        'authorization_no' => $authRaw,
                        'amount_paid' => $line['amount_paid'] ?? null,
                        'paid_at' => $line['paid_at'] ?? null,
                    ];
                    continue;
                }

                /** @var \App\Models\InvoiceReconciliationItem $it */

                $paid = (float) ($line['amount_paid'] ?? 0);
                $adj  = (float) ($it->adjustment_amount ?? 0);
                $bill = (float) ($it->amount_billed ?? 0);

                $balance = max(0, round($bill - $paid - $adj, 2));

                // status automático
                $status = 'pending';
                if ($paid <= 0) $status = 'pending';
                elseif ($balance <= 0.00001) $status = 'paid';
                else $status = 'partial';

                $it->amount_paid = $paid;
                $it->paid_at = $this->parsePaidAt($line['paid_at'] ?? null);
                $it->claim_no = $line['claim_no'] ?? null;
                $it->reason_code = $line['reason_code'] ?? null;
                $it->notes = $line['notes'] ?? null;
                $it->balance = $balance;
                $it->status = $status;

                // opcional: guardar raw/confidence si existen
                $it->raw = $line; // requiere cast array/json en el modelo
                $it->match_confidence = $line['match_confidence'] ?? null;

                $it->save();

                $matched++;
            }

            // 6) Guardar run resultado
            if ($runId) {
                InvoiceReconciliationRun::whereKey($runId)->update([
                    'status' => 'prefilled',
                    'matched_count' => $matched,
                    'error' => null,
                ]);
            }

            logger()->info('auto_reconciliation_apply', [
                'rec_id' => $rec->id,
                'invoice_id' => $rec->invoice_id,
                'ai_lines' => count($aiLines),
                'rec_items' => $rec->items->count(),
                'matched' => $matched,
                'unmatched_count' => count($unmatched),
                'unmatched_sample' => array_slice($unmatched, 0, 10),
            ]);

            // IMPORTANTÍSIMO: redirect para refrescar payload (si no, verás data vieja en Blade)
            return redirect()
                ->route('invoices.view', $invoice)
                ->with('success', "Conciliación automática prellenada. Coincidencias: {$matched}. Revisa y confirma.");
        } catch (\Throwable $e) {

            if ($runId) {
                InvoiceReconciliationRun::whereKey($runId)->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }

            return back()->withErrors(['reconciliation' => 'Error procesando PDF con OpenAI: ' . $e->getMessage()]);
        }
    }

    /**
     * Normaliza autorización: quita guiones/espacios y deja A-Z0-9
     */
    private function normAuth(?string $s): string
    {
        $s = strtoupper(trim((string) $s));
        return preg_replace('/[^A-Z0-9]/', '', $s) ?: '';
    }

    /**
     * Devuelve los últimos 5 dígitos numéricos del authorization_no.
     * Usado como fallback cuando el PDF trae la autorización con prefijos
     * como "2025-34-0045923" y el analista la registró como "45923".
     */
    private function authSuffix(?string $s): string
    {
        $digits = preg_replace('/\D/', '', (string) $s);
        if ($digits === '' || strlen($digits) < 5) return '';
        return substr($digits, -5);
    }

    /**
     * Parse paid_at desde el PDF (OpenAI) hacia Y-m-d
     * soporta m/d/Y y d/m/Y
     */
    private function parsePaidAt(?string $s): ?string
    {
        $s = trim((string) $s);
        if ($s === '') return null;

        try {
            return \Carbon\Carbon::createFromFormat('m/d/Y', $s)->toDateString();
        } catch (\Throwable $e) {
            try {
                return \Carbon\Carbon::createFromFormat('d/m/Y', $s)->toDateString();
            } catch (\Throwable $e2) {
                return null;
            }
        }
    }

    private function extractPdfWithOpenAI(Invoice $invoice, string $publicPath): array
    {
        $apiKey = config('services.openai.key'); // ponlo en config/services.php
        $model  = config('services.openai.model', 'gpt-4.1'); // o el que uses con visión/PDF

        // 1) Subir PDF a OpenAI Files API
        $fileAbs = Storage::disk('public')->path($publicPath);

        $upload = Http::withToken($apiKey)
            ->attach('file', file_get_contents($fileAbs), basename($fileAbs))
            ->post('https://api.openai.com/v1/files', [
                'purpose' => 'user_data',
            ]);
        if (!$upload->ok()) {
            throw new \RuntimeException('OpenAI file upload failed: ' . $upload->body());
        }

        $fileId = $upload->json('id');
        // 2) Responses API con Structured Outputs + PDF
        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'belongs_to_doctor' => ['type' => 'boolean'],
                'doctor' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'name' => ['type' => ['string', 'null']],
                        'rnc'  => ['type' => ['string', 'null']],
                    ],
                    'required' => ['name', 'rnc']
                ],
                'insurer' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'name' => ['type' => ['string', 'null']],
                    ],
                    'required' => ['name']
                ],
                'lines' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'authorization_no' => ['type' => ['string', 'null']],
                            'claim_no' => ['type' => ['string', 'null']],
                            'amount_paid' => ['type' => ['number', 'null']],
                            'paid_at' => ['type' => ['string', 'null']], // YYYY-MM-DD
                            'reason_code' => ['type' => ['string', 'null']],
                            'notes' => ['type' => ['string', 'null']],
                        ],
                        'required' => ['authorization_no', 'claim_no', 'amount_paid', 'paid_at', 'reason_code', 'notes']
                    ]
                ],
            ],
            'required' => ['belongs_to_doctor', 'doctor', 'insurer', 'lines'],
        ];

        $prompt = "Analiza el PDF de pagos de una aseguradora y extrae reclamaciones.
            Debes validar si el PDF corresponde al doctor y aseguradora esperada.

            Doctor esperado: {$invoice->doctor?->full_name}
            Aseguradora esperada: {$invoice->insurer?->name}

            Reglas:
            - authorization_no es obligatorio por línea si aparece en el PDF.
            - amount_paid es el monto pagado por esa autorización.
            - Si hay código de rechazo/razón, ponlo en reason_code.
            - Si no hay un campo, usa null.
            Devuelve SOLO JSON según el schema.";

        try {
            $resp = Http::withToken($apiKey)
                ->timeout(120)          // tiempo total
                ->connectTimeout(10)    // tiempo máximo para conectar
                ->retry(2, 1000)        // 2 reintentos, 1s entre intentos
                ->throw()
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $model,
                    'input' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'input_text', 'text' => $prompt],
                                ['type' => 'input_file', 'file_id' => $fileId],
                            ],
                        ],
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'reconciliation_pdf_extract',
                            'schema' => $schema,
                            'strict' => true,
                        ],
                    ],
                ]);
            if (!$resp->ok()) {
                throw new \RuntimeException('OpenAI responses failed: ' . $resp->body());
            }

            // Dependiendo del SDK/forma, aquí sacas el JSON final.
            // Con HTTP raw, normalmente viene en output_text o en un campo JSON.
            $dataText = $resp->json('output_text'); // si tu respuesta trae output_text
            if (!$dataText) {
                // fallback: intenta extraer del primer output
                $dataText = data_get($resp->json(), 'output.0.content.0.text');
            }

            $parsed = json_decode($dataText, true);

            if (!is_array($parsed)) {
                throw new \RuntimeException('OpenAI returned non-JSON result.');
            }

            return $parsed;
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
}
