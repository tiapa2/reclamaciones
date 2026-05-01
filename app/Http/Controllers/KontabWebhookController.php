<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recibe webhooks de kontab-erp:
 *   - invoice.dgii.accepted
 *   - invoice.dgii.rejected
 *
 * Headers esperados:
 *   X-Kontab-Event       (ej. "invoice.dgii.accepted")
 *   X-Kontab-Timestamp   (unix seconds)
 *   X-Kontab-Signature   "sha256=<hex(HMAC-SHA256(webhook_secret, ts + '.' + rawBody))>"
 */
class KontabWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            Log::warning('Kontab webhook: firma inválida', [
                'event' => $request->header('X-Kontab-Event'),
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false, 'reason' => 'bad_signature'], 401);
        }

        $event = $request->header('X-Kontab-Event');
        $payload = $request->json()->all();
        $data = $payload['data'] ?? [];
        $kontabInvoiceId = $data['invoice_id'] ?? null;

        if (! $kontabInvoiceId) {
            return response()->json(['ok' => false, 'reason' => 'missing_invoice_id'], 422);
        }

        $invoice = Invoice::where('kontab_invoice_id', $kontabInvoiceId)->first();
        if (! $invoice) {
            // No es nuestra — devolvemos 200 para que kontab no reintente.
            return response()->json(['ok' => true, 'matched' => false]);
        }

        $newStatus = match ($event) {
            'invoice.dgii.accepted' => 'accepted',
            'invoice.dgii.rejected' => 'rejected',
            default => null,
        };

        if (! $newStatus) {
            return response()->json(['ok' => true, 'ignored_event' => $event]);
        }

        $invoice->update([
            'kontab_dgii_status' => $newStatus,
            'kontab_dgii_response_at' => now(),
            'kontab_dgii_response' => $data,
            // Algunos campos vienen actualizados sólo en el accept (track_id confirmado, etc.)
            'kontab_track_id' => $data['track_id'] ?? $invoice->kontab_track_id,
            'kontab_security_code' => $data['security_code'] ?? $invoice->kontab_security_code,
            'kontab_ncf' => $data['ncf'] ?? $invoice->kontab_ncf,
        ]);

        Log::info('Kontab webhook procesado', [
            'event' => $event,
            'invoice_id' => $invoice->id,
            'kontab_invoice_id' => $kontabInvoiceId,
            'new_status' => $newStatus,
        ]);

        return response()->json(['ok' => true]);
    }

    private function verifySignature(Request $request): bool
    {
        $secret = (string) env('KONTAB_WEBHOOK_SECRET', '');
        if ($secret === '') {
            return false;
        }

        $ts = (string) $request->header('X-Kontab-Timestamp', '');
        $sig = (string) $request->header('X-Kontab-Signature', '');
        if ($ts === '' || $sig === '') {
            return false;
        }

        // Anti-replay: ventana de 5 minutos.
        if (abs(time() - (int) $ts) > 300) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $ts.'.'.$request->getContent(), $secret);

        return hash_equals($expected, $sig);
    }
}
