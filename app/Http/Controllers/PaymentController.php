<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoicePaymentRequest;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Support\DoctorScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
        use DoctorScope;
    public function index(Request $request)
    {
        $doctorId = $this->scopedDoctorId();

        

        if ($doctorId) {
            $q = trim((string)$request->get('q'));
    
            $payments = InvoicePayment::with(['invoice.doctor', 'invoice.insurer', 'createdBy'])
                ->whereHas('invoice', fn($i) => $i->where('doctor_id', $doctorId))
                ->when($q !== '', function ($query) use ($q) {
                    $query->whereHas('invoice', function ($inv) use ($q) {
                        $inv->where('ncf_number', 'like', "%{$q}%")
                            ->orWhereHas('insurer', fn($i) => $i->where('name', 'like', "%{$q}%"));
                    })->orWhere('reference_no', 'like', "%{$q}%");
                })
                ->orderByDesc('id')
                ->paginate(12)
                ->withQueryString();
        }else{
            $q = trim((string)$request->get('q'));
    
            $payments = InvoicePayment::with(['invoice.doctor', 'invoice.insurer', 'createdBy'])
                ->when($q !== '', function ($query) use ($q) {
                    $query->whereHas('invoice', function ($inv) use ($q) {
                        $inv->where('ncf_number', 'like', "%{$q}%")
                            ->orWhereHas('doctor', fn($d) => $d->where('full_name', 'like', "%{$q}%"))
                            ->orWhereHas('insurer', fn($i) => $i->where('name', 'like', "%{$q}%"));
                    })->orWhere('reference_no', 'like', "%{$q}%");
                })
                ->orderByDesc('id')
                ->paginate(12)
                ->withQueryString();
        }


        return view('payments.index', compact('payments', 'q'));
    }

    public function invoiceSearch(Request $request)
    {
        $term = trim((string)$request->query('q'));

        $invoices = Invoice::with(['doctor:id,full_name', 'insurer:id,name'])
            ->when($term !== '', function ($query) use ($term) {
                $query->where('ncf_number', 'like', "%{$term}%")
                    ->orWhereHas('doctor', fn($d) => $d->where('full_name', 'like', "%{$term}%"))
                    ->orWhereHas('insurer', fn($i) => $i->where('name', 'like', "%{$term}%"));
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'ncf_number', 'doctor_id', 'insurer_id', 'total_amount', 'paid_amount', 'payment_status', 'status']);

        return response()->json([
            'ok' => true,
            'invoices' => $invoices->map(fn($inv) => [
                'id' => $inv->id,
                'ncf_number' => $inv->ncf_number,
                'doctor' => $inv->doctor?->full_name,
                'insurer' => $inv->insurer?->name,
                'total' => (float)$inv->total_amount,
                'paid' => (float)$inv->paid_amount,
                'balance' => max(0, (float)$inv->total_amount - (float)$inv->paid_amount),
                'payment_status' => $inv->payment_status,
                'status' => $inv->status,
            ]),
        ]);
    }

    public function store(StoreInvoicePaymentRequest $request)
    {
        $data = $request->validated();

        $invoiceId = (int)($data['invoice_id'] ?? 0);
        abort_if(!$invoiceId, 422, 'Debe seleccionar una factura.');

        DB::transaction(function () use ($invoiceId, $data) {

            /** @var Invoice $invoice */
            $invoice = Invoice::whereKey($invoiceId)->lockForUpdate()->firstOrFail();

            // Opcional: bloquear pagos a facturas anuladas
            if ($invoice->status === 'void') {
                abort(422, 'No se pueden registrar pagos a una factura anulada.');
            }

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

        return redirect()->route('payments.index')->with('success', 'Pago registrado correctamente.');
    }

    public function destroy(InvoicePayment $payment)
    {
        DB::transaction(function () use ($payment) {
            $invoice = Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();
            $payment->delete();
            $this->refreshInvoicePaymentTotals($invoice);
        });

        return back()->with('success', 'Pago eliminado correctamente.');
    }

    private function refreshInvoicePaymentTotals(Invoice $invoice): void
    {
        $paid  = (float) $invoice->payments()->sum('amount');
        $total = (float) $invoice->total_amount;

        $status = 'unpaid';
        $paidAt = null;

        if ($paid > 0 && $paid < $total) {
            $status = 'partial';
        } elseif ($total > 0 && $paid >= $total) {
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
