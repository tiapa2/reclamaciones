<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFactoringRequest;
use App\Models\Factoring;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FactoringController extends Controller
{
public function index(Request $request)
{
    $q = Factoring::query()
        ->with(['invoice', 'doctor', 'insurer']);

    // Buscador tipo "Pagos"
    if ($request->filled('q')) {
        $term = trim($request->q);

        $q->where(function ($qq) use ($term) {
            // por invoice_id si viene numérico
            if (ctype_digit($term)) {
                $qq->orWhere('invoice_id', (int)$term);
                $qq->orWhere('id', (int)$term);
            }

            // por referencia / notas del factoring (si existen)
            $qq->orWhere('reference_no', 'like', "%{$term}%")
               ->orWhere('notes', 'like', "%{$term}%");

            // relaciones
            $qq->orWhereHas('invoice', fn($x) => $x->where('ncf_number', 'like', "%{$term}%"));
            $qq->orWhereHas('doctor', fn($x) => $x->where('full_name', 'like', "%{$term}%"));
            $qq->orWhereHas('insurer', fn($x) => $x->where('name', 'like', "%{$term}%"));
        });
    }

    // filtros simples que ya tenías (si quieres usarlos luego con selects)
    if ($request->filled('doctor_id')) {
        $q->where('doctor_id', (int) $request->doctor_id);
    }
    if ($request->filled('insurer_id')) {
        $q->where('insurer_id', (int) $request->insurer_id);
    }
    if ($request->filled('status')) {
        $q->where('status', $request->status);
    }

    $factorings = $q->orderByDesc('id')
        ->paginate(15)
        ->withQueryString();

    return view('factoring.index', compact('factorings'));
}


    /**
     * Crear factoring desde una factura
     */
    public function create(Request $request)
{
    $invoice = null;

    $invoiceId = $request->query('invoice_id');

    if ($invoiceId) {
        $invoice = Invoice::with(['doctor', 'insurer'])
            ->where('status', '!=', 'void')
            ->findOrFail($invoiceId);

        // Opcional: si no quieres permitir factorizadas
        // if ($invoice->factoring) {
        //     return redirect()->route('factorings.create')
        //         ->withErrors(['invoice_id' => 'Esta factura ya tiene factoring.']);
        // }
    }

    return view('factoring.create', compact('invoice'));
}

    public function store(StoreFactoringRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data) {

            $invoice = Invoice::with(['doctor','insurer','factoring'])
                ->lockForUpdate()
                ->findOrFail($data['invoice_id']);

            // Reglas básicas
            if ($invoice->status === 'void') {
                abort(422, 'No se puede factorizar una factura anulada.');
            }

            if ($invoice->factoring) {
                abort(422, 'Esta factura ya tiene un factoring registrado.');
            }

            $total = (float) $invoice->total_amount;
            $discountRate = (float) $data['discount_rate']; // ISR
            $commissionRate = (float) $data['commission_rate'];

            $discountAmount = round($total * ($discountRate / 100), 2); // ISR
            $totalAfterISR = $total - $discountAmount;
            $commissionAmount = round($totalAfterISR * ($commissionRate / 100), 2);

            $netToDoctor = round($total - $discountAmount - $commissionAmount, 2);
            if ($netToDoctor < 0) {
                abort(422, 'El neto a entregar al doctor no puede ser negativo. Revisa tasas.');
            }

            $expectedProfit = round($commissionAmount, 2);

            $purchaseDate = \Carbon\Carbon::parse($data['purchase_date'])->startOfDay();
            $termDays = (int) $data['term_days'];
            $dueDate = (clone $purchaseDate)->addDays($termDays);

            $factoring = Factoring::create([
                'invoice_id' => $invoice->id,
                'doctor_id' => $invoice->doctor_id,
                'insurer_id' => $invoice->insurer_id,
                'created_by' => auth()->id(),

                'purchase_date' => $purchaseDate->toDateString(),
                'term_days' => $termDays,
                'due_date' => $dueDate->toDateString(),

                'invoice_total' => $total,

                'discount_rate' => $discountRate,
                'commission_rate' => $commissionRate,

                'discount_amount' => $discountAmount,
                'commission_amount' => $commissionAmount,
                'net_to_doctor' => $netToDoctor,
                'expected_profit' => $expectedProfit,

                'status' => 'active',
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            return redirect()
                ->route('factorings.show', $factoring)
                ->with('success', 'Factoring creado correctamente.');
        });
    }

    public function show(Factoring $factoring)
    {
        $factoring->load(['invoice', 'doctor', 'insurer', 'createdBy']);
        return view('factoring.show', compact('factoring'));
    }

    public function markCollected(Factoring $factoring)
    {
        if ($factoring->status !== 'active') {
            return back()->with('error', 'Solo puedes marcar como cobrado un factoring activo.');
        }

        $factoring->status = 'collected';
        $factoring->collected_at = now()->toDateString();
        $factoring->save();

        return back()->with('success', 'Factoring marcado como cobrado.');
    }

    public function cancel(Factoring $factoring)
    {
        if ($factoring->status === 'collected') {
            return back()->with('error', 'No puedes cancelar un factoring ya cobrado.');
        }

        $factoring->status = 'cancelled';
        $factoring->save();

        return back()->with('success', 'Factoring cancelado.');
    }
}
