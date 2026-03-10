<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Insurer;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceQueryController extends Controller
{
    public function index()
    {
        // Para los selects del filtro
        $doctors = Doctor::orderBy('full_name')->get(['id','full_name']);
        $insurers = Insurer::orderBy('name')->get(['id','name']);

        return view('invoices.query', compact('doctors', 'insurers'));
    }

    public function search(Request $request)
    {
        $doctorId = $request->integer('doctor_id');
        $insurerId = $request->integer('insurer_id');

        $q = Invoice::query()
            ->with(['doctor:id,full_name', 'insurer:id,name', 'ncfType:id,name,prefix'])
            ->when($doctorId, fn($x) => $x->where('doctor_id', $doctorId))
            ->when($insurerId, fn($x) => $x->where('insurer_id', $insurerId))
            ->orderByDesc('invoice_date')
            ->limit(200)
            ->get()
            ->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'doctor' => $inv->doctor?->full_name,
                    'insurer' => $inv->insurer?->name,
                    'ncf' => $inv->ncf_number,
                    'date' => optional($inv->invoice_date)->format('Y-m-d'),
                    'status' => $inv->status,
                    'total' => (float) $inv->total_amount,
                ];
            });

        return response()->json([
            'ok' => true,
            'invoices' => $q,
        ]);
    }
}
