<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceItemController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        abort_if($invoice->isLocked(), 403, 'Factura electrónica ya emitida: no se pueden agregar líneas.');

        $isArs = ($invoice->invoice_type ?? 'ars') === 'ars';

        if ($isArs) {
            $data = $request->validate([
                'service_date' => ['required', 'date'],
                'patient_name' => ['required', 'string', 'max:255'],
                'affiliate_no' => ['nullable', 'string', 'max:100'],
                'authorization_no' => ['nullable', 'string', 'max:100'],
                'amount' => ['required', 'numeric', 'min:0.01'],
            ]);
            $data['description'] = null;
        } else {
            $data = $request->validate([
                'service_date' => ['required', 'date'],
                'description' => ['required', 'string', 'max:500'],
                'amount' => ['required', 'numeric', 'min:0.01'],
            ]);
            $data['patient_name'] = null;
            $data['affiliate_no'] = null;
            $data['authorization_no'] = null;
        }

        $item = DB::transaction(function () use ($invoice, $data) {
            $item = $invoice->items()->create($data);

            $invoice->total_amount = $invoice->items()->sum('amount');
            $invoice->save();

            return $item;
        });

        return response()->json([
            'ok' => true,
            'new_total' => (float) $invoice->total_amount,
            'item' => [
                'id' => $item->id,
                'service_date' => optional($item->service_date)->toDateString(),
                'description' => $item->description,
                'patient_name' => $item->patient_name,
                'affiliate_no' => $item->affiliate_no,
                'authorization_no' => $item->authorization_no,
                'amount' => (float) $item->amount,
                '_edit' => false,
            ],
        ]);
    }

    public function update(Request $request, InvoiceItem $item)
    {
        $invoice = $item->invoice;
        abort_if($invoice->isLocked(), 403, 'Factura electrónica ya emitida: no se puede editar.');

        $isArs = ($invoice->invoice_type ?? 'ars') === 'ars';

        if ($isArs) {
            $data = $request->validate([
                'service_date' => ['required', 'date'],
                'patient_name' => ['required', 'string', 'max:255'],
                'affiliate_no' => ['nullable', 'string', 'max:100'],
                'authorization_no' => ['nullable', 'string', 'max:100'],
                'amount' => ['required', 'numeric', 'min:0.01'],
            ]);
            $data['description'] = null;
        } else {
            $data = $request->validate([
                'service_date' => ['required', 'date'],
                'description' => ['required', 'string', 'max:500'],
                'amount' => ['required', 'numeric', 'min:0.01'],
            ]);
            $data['patient_name'] = null;
            $data['affiliate_no'] = null;
            $data['authorization_no'] = null;
        }

        DB::transaction(function () use ($item, $data, $invoice) {
            $item->update($data);

            $invoice->total_amount = $invoice->items()->lockForUpdate()->sum('amount');
            $invoice->save();
        });

        return response()->json(['ok' => true]);
    }

    public function destroy(InvoiceItem $item)
    {
        $newTotal = null;

        DB::transaction(function () use ($item, &$newTotal) {
            $invoice = $item->invoice()->lockForUpdate()->first();
            abort_if($invoice->isLocked(), 403, 'Factura electrónica ya emitida: no se puede eliminar líneas.');

            $item->delete();

            $newTotal = (float) $invoice->items()->sum('amount');
            $invoice->total_amount = $newTotal;
            $invoice->save();
        });

        return response()->json(['ok' => true, 'new_total' => $newTotal]);
    }
}
