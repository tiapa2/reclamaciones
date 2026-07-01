<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Doctor;
use App\Models\DoctorNcfAuthorization;
use App\Models\Insurer;
use App\Models\Invoice;
use App\Models\NcfType;
use App\Services\KontabClient;
use App\Support\DoctorScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    use DoctorScope;

    public function index(Request $request)
    {
        $doctorId = $this->scopedDoctorId();

        $q = Invoice::query()->with(['doctor', 'insurer', 'ncfType']);

        if ($doctorId) {
            $q->where('doctor_id', $doctorId);
            $invoices = $q->orderByDesc('id')->paginate(10)->withQueryString();

            // IMPORTANT: en doctor_id select, si es doctor, no debería poder elegir otro doctor
            // así que en vista, ocultas el selector de doctor o lo fijas al suyo.

            return view('invoices.index', compact('invoices'));
        } else {
            $doctors = Doctor::orderBy('full_name')->get(['id', 'full_name']);
            $insurers = Insurer::orderBy('name')->get(['id', 'name']);
            $ncfTypes = NcfType::where('active', true)->orderBy('id')->get(['id', 'name', 'prefix']);

            // Si quieres listar facturas debajo (opcional)
            $invoices = Invoice::with(['doctor', 'insurer', 'ncfType'])
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString();

            return view('invoices.index', compact('doctors', 'insurers', 'ncfTypes', 'invoices'));
        }
        // Para el formulario (selects)

    }

    public function store(StoreInvoiceRequest $request)
    {
        $data = $request->validated();

        $invoice = DB::transaction(function () use ($data) {
            $doctor = Doctor::findOrFail($data['doctor_id']);
            $useElectronic = (bool) $doctor->e_invoicing_enabled;

            // ─── Bifurcación: NCF local (default) vs facturación electrónica vía kontab-erp.
            // Sólo se reserva NCF local si NO va a kontab. Si va a kontab, kontab asigna NCF.
            $ncfSeq = null;
            $ncfNumber = null;
            $ncfTypeIdForRecord = $data['ncf_type_id'];

            if (! $useElectronic) {
                $auth = DoctorNcfAuthorization::where('doctor_id', $data['doctor_id'])
                    ->where('ncf_type_id', $data['ncf_type_id'])
                    ->where('active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $auth) {
                    abort(422, 'El médico no tiene autorización NCF activa para este tipo.');
                }
                if ($auth->expires_at && now()->toDateString() > $auth->expires_at->toDateString()) {
                    abort(422, 'La autorización NCF está vencida.');
                }
                $next = (int) ($auth->current_number ?? $auth->from_number);
                if ($next < (int) $auth->from_number || $next > (int) $auth->to_number) {
                    abort(422, 'No hay NCF disponibles en el rango autorizado.');
                }

                $type = NcfType::findOrFail($data['ncf_type_id']);
                $ncfSeq = $next;
                $ncfNumber = $type->prefix.str_pad((string) $next, 8, '0', STR_PAD_LEFT);
            }

            $total = collect($data['items'])->sum(fn ($it) => (float) $it['amount']);

            // 1) Crear invoice (con NCF local si aplica, sin si va a kontab)
            $invoice = Invoice::create([
                'doctor_id' => $data['doctor_id'],
                'insurer_id' => $data['insurer_id'],
                'ncf_type_id' => $ncfTypeIdForRecord,
                'invoice_date' => $data['invoice_date'],
                'invoice_type' => $data['invoice_type'],
                'ncf_seq' => $ncfSeq,
                'ncf_number' => $ncfNumber,
                'total_amount' => $total,
                'status' => 'issued',
                'created_by' => auth()->id(),
            ]);

            // 2) Items (mismo formato que antes)
            $isArs = $invoice->invoice_type === 'ars';
            $rows = collect($data['items'])->map(fn ($it) => [
                'invoice_id' => $invoice->id,
                'service_date' => $it['service_date'],
                'description' => ! $isArs ? ($it['description'] ?? null) : null,
                'patient_name' => $isArs ? ($it['patient_name'] ?? null) : null,
                'affiliate_no' => $isArs ? ($it['affiliate_no'] ?? null) : null,
                'authorization_no' => $isArs ? ($it['authorization_no'] ?? null) : null,
                'procedure_id' => $it['procedure_id'] ?? null,
                'amount' => $it['amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();
            $invoice->items()->insert($rows);

            // 3) Incrementar correlativo SOLO si usamos NCF local
            if (! $useElectronic && isset($auth)) {
                $auth->current_number = $next + 1;
                $auth->save();
            }

            return $invoice;
        });

        // ─── Si el doctor tiene facturación electrónica, despacha a kontab-erp DESPUÉS
        // del commit (para no envolver llamadas HTTP en una transacción larga).
        $doctor = $invoice->doctor;
        if ($doctor && $doctor->e_invoicing_enabled) {
            try {
                $invoice->loadMissing(['insurer', 'items', 'ncfType']);
                $client = new KontabClient($doctor);
                $contactId = $client->upsertContactForInsurer($invoice->insurer);
                $resp = $client->createSalesInvoice($invoice, $contactId);

                $invoice->update([
                    'kontab_contact_id' => $contactId,
                    'kontab_invoice_id' => $resp['id'],
                    'kontab_ncf' => $resp['ncf'],
                    'kontab_track_id' => $resp['track_id'],
                    'kontab_security_code' => $resp['security_code'],
                    'kontab_dgii_status' => $resp['dgii_status'] ?? 'pending',
                    'kontab_dgii_response' => $resp['raw'] ?? null,
                    'kontab_dgii_response_at' => now(),
                    // Reflejar el NCF de kontab también en ncf_number para consistencia visual.
                    'ncf_number' => $resp['ncf'] ?: $invoice->ncf_number,
                ]);

                Log::info('Factura electrónica creada en kontab-erp', [
                    'invoice_id' => $invoice->id,
                    'kontab_invoice_id' => $resp['id'],
                    'ncf' => $resp['ncf'],
                ]);
            } catch (\Throwable $e) {
                Log::error('Falló envío a kontab-erp', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->route('invoices.index')
                    ->with('warning', "Factura #{$invoice->id} creada localmente, pero falló el envío a kontab-erp: {$e->getMessage()}. Reintenta desde el detalle.");
            }
        }

        return redirect()->route('invoices.index')->with('success', 'Factura creada correctamente.');
    }

    /**
     * Reenvía a kontab-erp una factura de un médico electrónico que NO se creó allá
     * (kontab_invoice_id vacío), típicamente porque el primer intento falló.
     * No duplica: si ya tiene kontab_invoice_id, no reenvía.
     */
    public function resendToKontab(Invoice $invoice)
    {
        $invoice->loadMissing(['doctor', 'insurer', 'items', 'ncfType']);
        $doctor = $invoice->doctor;

        if (! $doctor || ! $doctor->e_invoicing_enabled) {
            return back()->with('warning', 'El médico de esta factura no es facturador electrónico.');
        }
        if ($invoice->kontab_invoice_id) {
            return back()->with('warning', "La factura ya existe en kontab-erp (ID {$invoice->kontab_invoice_id}). No se reenvía para evitar duplicados.");
        }
        if ($invoice->status === 'void') {
            return back()->with('warning', 'No se puede reenviar una factura anulada.');
        }

        try {
            $client = new KontabClient($doctor);
            $contactId = $client->upsertContactForInsurer($invoice->insurer);
            $resp = $client->createSalesInvoice($invoice, $contactId);

            $invoice->update([
                'kontab_contact_id' => $contactId,
                'kontab_invoice_id' => $resp['id'],
                'kontab_ncf' => $resp['ncf'],
                'kontab_track_id' => $resp['track_id'],
                'kontab_security_code' => $resp['security_code'],
                'kontab_dgii_status' => $resp['dgii_status'] ?? 'pending',
                'kontab_dgii_response' => $resp['raw'] ?? null,
                'kontab_dgii_response_at' => now(),
                'ncf_number' => $resp['ncf'] ?: $invoice->ncf_number,
            ]);

            Log::info('Factura reenviada a kontab-erp', [
                'invoice_id' => $invoice->id,
                'kontab_invoice_id' => $resp['id'],
                'ncf' => $resp['ncf'],
            ]);

            return back()->with('success', "Factura reenviada a kontab-erp. e-NCF: {$resp['ncf']} · DGII: {$resp['dgii_status']}.");
        } catch (\Throwable $e) {
            Log::error('Falló reenvío a kontab-erp', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('warning', "No se pudo reenviar a kontab-erp: {$e->getMessage()}");
        }
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->isLocked()) {
            abort(403, 'Esta factura ya fue enviada a Kontab y/o aceptada por DGII. Para corregir, debes anularla y emitir una nota de crédito.');
        }

        DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            $invoice->delete();
        });

        return redirect()->route('invoices.index')->with('success', 'Factura eliminada.');
    }

    public function previewNcf(Request $request)
    {
        $doctorId = (int) $request->query('doctor_id');
        $ncfTypeId = (int) $request->query('ncf_type_id');

        if (! $doctorId || ! $ncfTypeId) {
            return response()->json(['ok' => false, 'message' => 'doctor_id y ncf_type_id son requeridos.'], 422);
        }

        $auth = DoctorNcfAuthorization::where('doctor_id', $doctorId)
            ->where('ncf_type_id', $ncfTypeId)
            ->where('active', true)
            ->first();

        if (! $auth) {
            return response()->json(['ok' => false, 'message' => 'El médico no tiene autorización NCF activa.'], 404);
        }

        if ($auth->expires_at && now()->toDateString() > $auth->expires_at->toDateString()) {
            return response()->json(['ok' => false, 'message' => 'La autorización NCF está vencida.'], 422);
        }

        $next = (int) ($auth->current_number ?? $auth->from_number);

        if ($next < (int) $auth->from_number || $next > (int) $auth->to_number) {
            return response()->json(['ok' => false, 'message' => 'No hay NCF disponibles en el rango.'], 422);
        }

        $type = NcfType::findOrFail($ncfTypeId);
        $ncfNumber = $type->prefix.str_pad((string) $next, 8, '0', STR_PAD_LEFT);

        return response()->json([
            'ok' => true,
            'ncf_seq' => $next,
            'ncf_number' => $ncfNumber,
            'expires_at' => optional($auth->expires_at)->toDateString(),
        ]);
    }

    public function doctorData(Request $request)
    {
        $doctorId = (int) $request->query('doctor_id');
        if (! $doctorId) {
            return response()->json(['ok' => false, 'message' => 'doctor_id es requerido.'], 422);
        }

        $doctor = Doctor::with(['insurers' => function ($q) {
            // solo las que están vinculadas y con código (si quieres exigir código)
            $q->orderBy('name');
        }, 'ncfAuthorizations.ncfType'])->findOrFail($doctorId);

        $insurers = $doctor->insurers->map(fn ($i) => [
            'id' => $i->id,
            'name' => $i->name,
            'doctor_code' => $i->pivot->doctor_code,
        ])->values();

        // ─── Médico ELECTRÓNICO: el comprobante lo asigna kontab-erp (e-CF).
        // El selector muestra los tipos e-CF que kontab tiene con secuencia activa
        // y números disponibles (no las autorizaciones locales B01/B15).
        if ($doctor->e_invoicing_enabled) {
            try {
                $types = (new KontabClient($doctor))->availableEinvoiceTypes();
            } catch (\Throwable $e) {
                return response()->json([
                    'ok' => true,
                    'insurers' => $insurers,
                    'ncf_types' => [],
                    'electronic' => true,
                    'ncf_warning' => 'No se pudieron cargar los tipos e-CF de kontab-erp: '.$e->getMessage(),
                ]);
            }

            $ncfTypes = collect($types)->map(function ($t) {
                // Mapear a un NcfType local (por prefijo) para conservar el ncf_type_id.
                // No se consume NCF local: kontab asigna el número real al emitir.
                $local = NcfType::firstOrCreate(
                    ['prefix' => $t['code']],
                    ['name' => $t['name'], 'active' => true],
                );

                return [
                    'id' => $local->id,
                    'name' => $t['name'],
                    'prefix' => $t['code'],
                    'remaining' => $t['remaining'],
                    'next_ncf' => $t['next_ncf'],
                ];
            })->values();

            return response()->json([
                'ok' => true,
                'insurers' => $insurers,
                'ncf_types' => $ncfTypes,
                'electronic' => true,
            ]);
        }

        $ncfTypes = $doctor->ncfAuthorizations
            ->where('active', true)
            ->filter(fn ($a) => ! $a->expires_at || now()->toDateString() <= $a->expires_at->toDateString())
            ->map(fn ($a) => [
                'id' => $a->ncfType->id,
                'name' => $a->ncfType->name,
                'prefix' => $a->ncfType->prefix,
            ])
            ->unique('id')
            ->values();

        return response()->json([
            'ok' => true,
            'insurers' => $insurers,
            'ncf_types' => $ncfTypes,
            'electronic' => false,
        ]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'doctor',
            'insurer',
            'ncfType',
            'items',
            'createdBy',
            'payments' => function ($q) {
                $q->orderByDesc('payment_date')->orderByDesc('id');
            },
        ]);

        return response()->json([
            'ok' => true,
            'invoice' => [
                'id' => $invoice->id,
                'ncf_number' => $invoice->ncf_number,
                'invoice_date' => optional($invoice->invoice_date)->toDateString(),

                'doctor' => $invoice->doctor?->full_name,
                'insurer' => $invoice->insurer?->name,
                'ncf_type' => $invoice->ncfType?->name,

                // 👇 usa estos nombres para que tu modal no se rompa
                'total_amount' => (string) $invoice->total_amount,
                'paid_amount' => (string) ($invoice->paid_amount ?? 0),
                'status' => $invoice->status,
                'payment_status' => $invoice->payment_status ?? 'unpaid',
                'paid_at' => optional($invoice->paid_at)->toDateTimeString(),

                'invoice_type' => $invoice->invoice_type ?? 'ars',
                'created_by' => $invoice->createdBy?->name,

                'items' => $invoice->items->map(fn ($it) => [
                    'id' => $it->id,
                    'service_date' => optional($it->service_date)->toDateString(),
                    'description' => $it->description,
                    'patient_name' => $it->patient_name,
                    'affiliate_no' => $it->affiliate_no,
                    'authorization_no' => $it->authorization_no,
                    'amount' => (string) $it->amount,
                ])->values(),

                // 👇 pagos para el modal
                'payments' => $invoice->payments->map(fn ($p) => [
                    'id' => $p->id,
                    'payment_date' => optional($p->payment_date)->toDateString(),
                    'amount' => (string) $p->amount,
                    'method' => $p->method,
                    'reference_no' => $p->reference_no,
                    'notes' => $p->notes,
                ])->values(),
            ],
        ]);
    }

    public function view(Invoice $invoice)
    {
        $invoice->load([
            'doctor',
            'insurer',
            'items' => fn ($q) => $q->orderBy('id'),
            'payments' => fn ($q) => $q->orderByDesc('payment_date')->orderByDesc('id'),
            'reconciliations' => fn ($q) => $q->orderByDesc('id'),
            'reconciliations.items' => fn ($q) => $q->orderBy('id'),
        ]);

        $openRec = $invoice->reconciliations->firstWhere('status', 'open');
        $history = $invoice->reconciliations->where('status', '!=', 'open')->values(); // closed/canceled

        return view('invoices.view', compact('invoice', 'openRec', 'history'));
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['doctor', 'insurer', 'ncfType', 'items']);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))
            ->setPaper('letter'); // o 'a4'

        $filename = ($invoice->doctor?->full_name ?? 'Factura').'.pdf';

        return $pdf->stream($filename);
    }

    public function void(Invoice $invoice)
    {
        // Si tienes policies:
        // $this->authorize('void', $invoice);

        if ($invoice->status === 'void') {
            return back()->with('info', 'Esta factura ya está anulada.');
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => 'void',
                // opcional si tienes columnas:
                // 'voided_at' => now(),
                // 'voided_by' => auth()->id(),
            ]);
        });

        return back()->with('success', 'Factura anulada correctamente.');
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = Invoice::query()
            ->with(['doctor:id,full_name', 'insurer:id,name', 'factoring:id,invoice_id'])
            ->where('status', '!=', 'void');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q);
                }
                $w->orWhere('ncf_number', 'like', "%{$q}%")
                    ->orWhereHas('doctor', fn ($d) => $d->where('full_name', 'like', "%{$q}%"))
                    ->orWhereHas('insurer', fn ($i) => $i->where('name', 'like', "%{$q}%"));
            });
        }

        $invoices = $query->orderByDesc('id')
            ->limit(15)
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'ncf' => $inv->ncf_number,
                'doctor' => $inv->doctor?->full_name,
                'insurer' => $inv->insurer?->name,
                'date' => optional($inv->invoice_date)->toDateString(),
                'total' => (float) $inv->total_amount,
                'has_factoring' => (bool) $inv->factoring,
            ]);

        return response()->json([
            'ok' => true,
            'invoices' => $invoices,
        ]);
    }
}
