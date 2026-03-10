<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Support\DoctorScope;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use DoctorScope;

    public function __invoke(Request $request)
    {
        $doctorId = $this->scopedDoctorId();

        if ($doctorId) {
                 $todayStart = Carbon::today();
        $todayEnd   = Carbon::today()->endOfDay();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();

        // ===== KPIs (mes) =====
        $issuedCountMonth = Invoice::where('status', 'issued')
            ->where('doctor_id', $doctorId)
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->count();

        $voidCountMonth = Invoice::where('status', 'void')
            ->where('doctor_id', $doctorId)
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->count();

        $totalBilledMonth = (float) Invoice::where('status', 'issued')
            ->where('doctor_id', $doctorId)
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->sum('total_amount');

        $totalPaidMonth = (float) InvoicePayment::whereBetween('payment_date', [$monthStart, $monthEnd])
        ->whereHas('invoice', fn($i) => $i->where('doctor_id', $doctorId))
            ->sum('amount');

        // Si tu Invoice ya guarda paid_amount, esto es rápido:
        $totalOutstanding = (float) Invoice::whereIn('status', ['issued'])
            ->where('doctor_id', $doctorId)
            ->sum(DB::raw('GREATEST(total_amount - COALESCE(paid_amount, 0), 0)'));

        // ===== Hoy =====
        $issuedToday = Invoice::where('status', 'issued')
            ->where('doctor_id', $doctorId)
            ->whereBetween('invoice_date', [$todayStart, $todayEnd])
            ->count();

        $billedToday = (float) Invoice::where('status', 'issued')
            ->where('doctor_id', $doctorId)
            ->whereBetween('invoice_date', [$todayStart, $todayEnd])
            ->sum('total_amount');

        $paidToday = (float) InvoicePayment::whereBetween('payment_date', [$todayStart, $todayEnd])
        ->whereHas('invoice', fn($i) => $i->where('doctor_id', $doctorId))
            ->sum('amount');

        // ===== Listados =====
        $recentInvoices = Invoice::with(['doctor:id,full_name', 'insurer:id,name', 'ncfType:id,name'])
            ->where('doctor_id', $doctorId)
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $recentPayments = InvoicePayment::with([
                'invoice:id,ncf_number,total_amount,doctor_id,insurer_id',
                'invoice.doctor:id,full_name',
                'invoice.insurer:id,name',
                'createdBy:id,name',
            ])
            ->whereHas('invoice', fn($i) => $i->where('doctor_id', $doctorId))
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // ===== Top (mes) =====
        $topDoctors = Invoice::select('doctor_id', DB::raw('COUNT(*) as qty'), DB::raw('SUM(total_amount) as total'))
            ->where('status', 'issued')
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->groupBy('doctor_id')
            ->orderByDesc('total')
            ->with('doctor:id,full_name')
            ->limit(5)
            ->get();

        $topInsurers = Invoice::select('insurer_id', DB::raw('COUNT(*) as qty'), DB::raw('SUM(total_amount) as total'))
            ->where('status', 'issued')
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->groupBy('insurer_id')
            ->orderByDesc('total')
            ->with('insurer:id,name')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'issuedCountMonth',
            'voidCountMonth',
            'totalBilledMonth',
            'totalPaidMonth',
            'totalOutstanding',
            'issuedToday',
            'billedToday',
            'paidToday',
            'recentInvoices',
            'recentPayments',
            'topDoctors',
            'topInsurers',
            'monthStart',
            'monthEnd'
        ));
        }else{
             $todayStart = Carbon::today();
        $todayEnd   = Carbon::today()->endOfDay();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();

        // ===== KPIs (mes) =====
        $issuedCountMonth = Invoice::where('status', 'issued')
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->count();

        $voidCountMonth = Invoice::where('status', 'void')
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->count();

        $totalBilledMonth = (float) Invoice::where('status', 'issued')
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->sum('total_amount');

        $totalPaidMonth = (float) InvoicePayment::whereBetween('payment_date', [$monthStart, $monthEnd])
            ->sum('amount');

        // Si tu Invoice ya guarda paid_amount, esto es rápido:
        $totalOutstanding = (float) Invoice::whereIn('status', ['issued'])
            ->sum(DB::raw('GREATEST(total_amount - COALESCE(paid_amount, 0), 0)'));

        // ===== Hoy =====
        $issuedToday = Invoice::where('status', 'issued')
            ->whereBetween('invoice_date', [$todayStart, $todayEnd])
            ->count();

        $billedToday = (float) Invoice::where('status', 'issued')
            ->whereBetween('invoice_date', [$todayStart, $todayEnd])
            ->sum('total_amount');

        $paidToday = (float) InvoicePayment::whereBetween('payment_date', [$todayStart, $todayEnd])
            ->sum('amount');

        // ===== Listados =====
        $recentInvoices = Invoice::with(['doctor:id,full_name', 'insurer:id,name', 'ncfType:id,name'])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $recentPayments = InvoicePayment::with([
                'invoice:id,ncf_number,total_amount,doctor_id,insurer_id',
                'invoice.doctor:id,full_name',
                'invoice.insurer:id,name',
                'createdBy:id,name',
            ])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // ===== Top (mes) =====
        $topDoctors = Invoice::select('doctor_id', DB::raw('COUNT(*) as qty'), DB::raw('SUM(total_amount) as total'))
            ->where('status', 'issued')
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->groupBy('doctor_id')
            ->orderByDesc('total')
            ->with('doctor:id,full_name')
            ->limit(5)
            ->get();

        $topInsurers = Invoice::select('insurer_id', DB::raw('COUNT(*) as qty'), DB::raw('SUM(total_amount) as total'))
            ->where('status', 'issued')
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->groupBy('insurer_id')
            ->orderByDesc('total')
            ->with('insurer:id,name')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'issuedCountMonth',
            'voidCountMonth',
            'totalBilledMonth',
            'totalPaidMonth',
            'totalOutstanding',
            'issuedToday',
            'billedToday',
            'paidToday',
            'recentInvoices',
            'recentPayments',
            'topDoctors',
            'topInsurers',
            'monthStart',
            'monthEnd'
        ));
        }
       
    }
}
