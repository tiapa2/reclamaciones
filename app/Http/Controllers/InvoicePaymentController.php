<?php

// app/Http/Controllers/InvoicePaymentController.php
namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoicePaymentRequest;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;

class InvoicePaymentController extends Controller
{
    public function store(StoreInvoicePaymentRequest $request, Invoice $invoice)
    {
        $data = $request->validated();

        DB::transaction(function () use ($invoice, $data) {

            // Recalcular total pagado actual (por seguridad)
            $paidNow = (float) $invoice->payments()->sum('amount');
            $balance = max(0, (float)$invoice->total_amount - $paidNow);

            $amount = (float) $data['amount'];

            if ($amount > $balance) {
                abort(422, "El pago ({$amount}) no puede ser mayor al balance pendiente ({$balance}).");
            }

            InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'method' => $data['method'],
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $this->refreshInvoicePaymentTotals($invoice);
        });

        return back()->with('success', 'Pago registrado correctamente.');
    }

    public function destroy(Invoice $invoice, InvoicePayment $payment)
    {
        // evitar borrar pagos que no pertenecen a esa factura
        abort_unless($payment->invoice_id === $invoice->id, 404);

        DB::transaction(function () use ($invoice, $payment) {
            $payment->delete();
            $this->refreshInvoicePaymentTotals($invoice);
        });

        return back()->with('success', 'Pago eliminado correctamente.');
    }

    private function refreshInvoicePaymentTotals(Invoice $invoice): void
    {
        // Reload y recalcular
        $invoice->refresh();

        $paid = (float) $invoice->payments()->sum('amount');

        
        $total = (float) $invoice->total_amount;
        
        $status = 'unpaid';
        $paidAt = null;

        if ($paid > 0 && $paid < $total) {
            $status = 'partial';
        } elseif ($paid >= $total && $total > 0) {
            $status = 'paid';
            $paidAt = now();
        }
        $invoice->update([
            'paid_amount' => $paid,
            'payment_status' => $status,
            'paid_at' => $paidAt,
        ]);
    }
}
