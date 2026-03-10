<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreInsurerRequest;
use App\Http\Requests\UpdateInsurerRequest;
use App\Models\Insurer;
use Illuminate\Http\Request;

class InsurerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $insurers = Insurer::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('rnc', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('insurers.index', compact('insurers', 'q'));
    }

    public function store(StoreInsurerRequest $request)
    {
        Insurer::create($request->validated());

        return redirect()
            ->route('insurers.index')
            ->with('success', 'Aseguradora creada correctamente.');
    }

    public function update(UpdateInsurerRequest $request, Insurer $insurer)
    {
        $insurer->update($request->validated());

        return redirect()
            ->route('insurers.index')
            ->with('success', 'Aseguradora actualizada correctamente.');
    }

    public function destroy(Insurer $insurer)
    {
        $insurer->delete();

        return redirect()
            ->route('insurers.index')
            ->with('success', 'Aseguradora eliminada.');
    }

    // No usaremos create/edit/show porque lo haremos con modal:
    public function create() { abort(404); }
    public function edit(Insurer $insurer) { abort(404); }
    public function show(Insurer $insurer) { abort(404); }
}
