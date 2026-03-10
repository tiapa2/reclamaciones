<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\Doctor;
use App\Models\Insurer;
use App\Models\NcfType;
use App\Models\DoctorNcfAuthorization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $doctors = Doctor::with(['insurers', 'ncfAuthorizations'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('full_name', 'like', "%{$q}%")
                        ->orWhere('rnc', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('institution', 'like', "%{$q}%")
                        ->orWhere('specialty', 'like', "%{$q}%");
                });
            })
            ->orderBy('full_name')
            ->paginate(10)
            ->withQueryString();

        $insurers = Insurer::orderBy('name')->get(['id', 'name']);

        $ncfTypes = NcfType::where('active', true)
            ->orderBy('id')
            ->get(['id', 'name', 'prefix']);

        return view('doctors.index', compact('doctors', 'q', 'insurers', 'ncfTypes'));
    }

    public function store(StoreDoctorRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {

            $existing = Doctor::withTrashed()->where('rnc', $data['rnc'])->first();

            if ($existing && $existing->trashed()) {
                $existing->restore();
                $existing->update(collect($data)->except(['insurers', 'ncf'])->toArray());

                $this->syncInsurers($existing, $data['insurers'] ?? []);
                $this->syncNcf($existing, $data['ncf'] ?? []);

                return;
            }

            $doctor = Doctor::create(collect($data)->except(['insurers', 'ncf'])->toArray());

            $this->syncInsurers($doctor, $data['insurers'] ?? []);
            $this->syncNcf($doctor, $data['ncf'] ?? []);
        });

        return redirect()->route('doctors.index')->with('success', 'Médico guardado correctamente.');
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor)
    {
        $data = $request->validated();

        DB::transaction(function () use ($doctor, $data) {
            $doctor->update(collect($data)->except(['insurers', 'ncf'])->toArray());

            $this->syncInsurers($doctor, $data['insurers'] ?? []);
            $this->syncNcf($doctor, $data['ncf'] ?? []);
        });

        return redirect()->route('doctors.index')->with('success', 'Médico actualizado correctamente.');
    }

    public function destroy(Doctor $doctor)
    {
        $doctor->delete();
        return redirect()->route('doctors.index')->with('success', 'Médico eliminado.');
    }

    private function syncInsurers(Doctor $doctor, array $insurers): void
    {
        // $insurers: [ insurer_id => 'codigo' ]

        $sync = collect($insurers)
            ->mapWithKeys(function ($code, $insurerId) {
                $code = is_string($code) ? trim($code) : $code;

                return [
                    (int) $insurerId => [
                        'doctor_code' => $code,
                    ]
                ];
            })
            ->filter(function ($pivot) {
                // elimina null, '' y '   '
                return filled($pivot['doctor_code'] ?? null);
            })
            ->all();

        $doctor->insurers()->sync($sync);
    }

    private function syncNcf(Doctor $doctor, array $ncf): void
    {
        $rows = [];

        foreach ($ncf as $ncfTypeId => $fields) {

            $from = isset($fields['from_number']) ? (int)$fields['from_number'] : 0;
            $to   = isset($fields['to_number']) ? (int)$fields['to_number'] : 0;

            // ✅ regla real de activación
            if ($from <= 0 || $to <= 0) {
                continue;
            }

            $rows[] = [
                'doctor_id' => $doctor->id,
                'ncf_type_id' => (int) $ncfTypeId,
                'from_number' => $from,
                'to_number' => $to,
                'requested_at' => $fields['requested_at'] ?? null,
                'expires_at' => $fields['expires_at'] ?? null,
                'active' => true,
            ];
        }

        // Tipos que deben quedar
        $keepTypeIds = collect($rows)->pluck('ncf_type_id')->all();

        // Si no hay NCF válidos → borrar todos
        if (empty($keepTypeIds)) {
            $doctor->ncfAuthorizations()->delete();
            return;
        }

        // Borra los que ya no vienen
        $doctor->ncfAuthorizations()
            ->whereNotIn('ncf_type_id', $keepTypeIds)
            ->delete();

        // Upsert
        foreach ($rows as $r) {
            $auth = DoctorNcfAuthorization::updateOrCreate(
                [
                    'doctor_id' => $doctor->id,
                    'ncf_type_id' => $r['ncf_type_id'],
                ],
                [
                    'from_number' => $r['from_number'],
                    'to_number' => $r['to_number'],
                    'requested_at' => $r['requested_at'],
                    'expires_at' => $r['expires_at'],
                    'active' => true,
                ]
            );

            // Inicializa correlativo solo una vez
            if (is_null($auth->current_number)) {
                $auth->current_number = $r['from_number'];
                $auth->save();
            }
        }
    }

    public function insurers(Doctor $doctor)
{
    return response()->json([
        'ok' => true,
        'insurers' => $doctor->insurers()
            ->orderBy('name')
            ->get(['insurers.id','insurers.name']),
    ]);
}
public function resetUserPassword(Request $request, Doctor $doctor)
{
    
    abort_unless(auth()->user()->hasRole('admin'), 403);

    if (!$doctor->user) {
        return back()->with('error', 'Este doctor no tiene usuario.');
    }

    $data = $request->validate([
        'password' => ['sometimes'],
    ]);

    // si no mandas password, generamos una temporal
    $newPassword = $request->input('password') ?: str()->random(10);

    $doctor->user->update([
        'password' => Hash::make($newPassword),
    ]);

    // aquí tú decides: mostrarla en pantalla o mandarla por email
    return back()->with('success', "Contraseña reseteada. Nueva: {$newPassword}");
}

public function storeUser(Request $request, Doctor $doctor)
{
    abort_unless(auth()->user()->hasRole('admin'), 403);

    if ($doctor->user_id) {
        return back()->with('error', 'Este doctor ya tiene un usuario asignado.');
    }

    if (!$doctor->email) {
        return back()->with('error', 'Este doctor no tiene email, no se puede crear usuario.');
    }

    // valida password y valida que el email no exista en users
    $data = $request->validate([
        'password' => ['required', Password::min(8)],
    ]);

    // si el email del doctor ya existe en users, no creamos usuario
    if (User::where('email', $doctor->email)->exists()) {
        return back()->with('error', 'Ya existe un usuario con este email. Usa otro email en el doctor o vincúlalo manualmente.');
    }

    $user = User::create([
        'name' => $doctor->full_name ?? 'Doctor',
        'email' => $doctor->email,
        'password' => Hash::make($data['password']),
    ]);

    $user->assignRole('doctor');

    $doctor->user_id = $user->id;
    $doctor->save();

    return back()->with('success', 'Usuario creado y asignado al doctor.');
}

}
