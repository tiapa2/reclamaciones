<?php

namespace App\Http\Controllers;

use App\Models\InvoiceReconciliationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceReconciliationItemController extends Controller
{
    public function update(Request $request, InvoiceReconciliationItem $item)
    {
        $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'adjustment_amount' => ['nullable', 'numeric'],
            'status' => ['required', 'in:pending,partial,paid,rejected,adjusted'],
            'paid_at' => ['nullable', 'date'],
            'claim_no' => ['nullable', 'string', 'max:255'],
            'reason_code' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // Solo editable si la conciliación está abierta
        if ($item->reconciliation?->status !== 'open') {
            return response()->json(['ok' => false, 'message' => 'Conciliación no editable.'], 422);
        }

        $paid = (float) $request->amount_paid;
        $adj  = (float) ($request->adjustment_amount ?? 0);
        $billed = (float) $item->amount_billed;

        $balance = round(max(0, $billed - $paid - $adj), 2);

        DB::transaction(function () use ($item, $request, $paid, $adj, $balance) {
            $item->update([
                'amount_paid' => $paid,
                'adjustment_amount' => $adj,
                'balance' => $balance,
                'status' => $request->status,
                'paid_at' => $request->paid_at,
                'claim_no' => $request->claim_no,
                'reason_code' => $request->reason_code,
                'notes' => $request->notes,
            ]);
        });

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $item->id,
                'amount_paid' => (string)$item->amount_paid,
                'adjustment_amount' => (string)$item->adjustment_amount,
                'balance' => (string)$item->balance,
                'status' => $item->status,
                'paid_at' => optional($item->paid_at)->toDateString(),
                'claim_no' => $item->claim_no,
                'reason_code' => $item->reason_code,
                'notes' => $item->notes,
            ]
        ]);
    }
}
