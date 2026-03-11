<?php

namespace App\Http\Controllers;

use App\Models\Factoring;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AnalystController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $analysts = User::role('analista')
            ->withCount(['createdInvoices', 'createdPayments', 'createdFactorings'])
            ->orderBy('name')
            ->paginate(20);

        return view('analysts.index', compact('analysts'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('analista');

        return back()->with('success', "Analista \"{$user->name}\" creado correctamente.");
    }

    public function show(Request $request, User $analyst)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        abort_unless($analyst->hasRole('analista'), 404);

        $tab = $request->get('tab', 'invoices');

        $invoices = $tab === 'invoices'
            ? Invoice::where('created_by', $analyst->id)
                ->with('doctor', 'insurer')
                ->latest()
                ->paginate(20)
            : null;

        $payments = $tab === 'payments'
            ? InvoicePayment::where('created_by', $analyst->id)
                ->with('invoice.doctor', 'invoice.insurer')
                ->latest()
                ->paginate(20)
            : null;

        $factorings = $tab === 'factorings'
            ? Factoring::where('created_by', $analyst->id)
                ->with('doctor', 'insurer', 'invoice')
                ->latest()
                ->paginate(20)
            : null;

        $counts = [
            'invoices'  => Invoice::where('created_by', $analyst->id)->count(),
            'payments'  => InvoicePayment::where('created_by', $analyst->id)->count(),
            'factorings' => Factoring::where('created_by', $analyst->id)->count(),
        ];

        return view('analysts.show', compact('analyst', 'tab', 'invoices', 'payments', 'factorings', 'counts'));
    }

    public function destroy(User $analyst)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        abort_unless($analyst->hasRole('analista'), 404);

        $analyst->delete();

        return back()->with('success', 'Analista eliminado.');
    }

    public function resetPassword(Request $request, User $analyst)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        abort_unless($analyst->hasRole('analista'), 404);

        $request->validate([
            'password' => ['sometimes', 'string', Password::min(8)],
        ]);

        $newPassword = $request->input('password') ?: str()->random(10);

        $analyst->update(['password' => Hash::make($newPassword)]);

        return back()->with('success', "Contraseña reseteada. Nueva: {$newPassword}");
    }
}
