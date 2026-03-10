<?php

namespace App\Http\Controllers;

use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceItemController extends Controller
{
    public function update(Request $request, InvoiceItem $item)
    {
        $data = $request->validate([
            'service_date'     => ['required','date'],
            'patient_name'     => ['required','string','max:255'],
            'affiliate_no'     => ['nullable','string','max:100'],
            'authorization_no' => ['nullable','string','max:100'],
            'amount'           => ['required','numeric','min:0.01'],
        ]);

        DB::transaction(function () use ($item, $data) {
            $item->update($data);

            // Recalcular total de la factura
            $invoice = $item->invoice()->lockForUpdate()->first();
            $newTotal = $invoice->items()->sum('amount');
            $invoice->total_amount = $newTotal;

            // Opcional: recalcular estado pago
            // $invoice->payment_status = $invoice->paid_amount >= $newTotal ? 'paid' : ($invoice->paid_amount > 0 ? 'partial' : 'unpaid');

            $invoice->save();
        });

        return response()->json(['ok' => true]);
    }

    public function destroy(InvoiceItem $item)
    {
        DB::transaction(function () use ($item) {
            $invoice = $item->invoice()->lockForUpdate()->first();

            $item->delete();

            $newTotal = $invoice->items()->sum('amount');
            $invoice->total_amount = $newTotal;
            $invoice->save();
        });

        return response()->json(['ok' => true]);
    }
}
