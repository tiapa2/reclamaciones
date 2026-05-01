<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Services\KontabClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Configuración de facturación electrónica por doctor.
 * El doctor (o un admin) pega su API key + secret de kontab-erp y activa el toggle.
 * Sólo se persiste si el "Probar conexión" pasa.
 */
class KontabBillingSettingsController extends Controller
{
    public function edit(Request $request)
    {
        $doctor = $this->resolveDoctor($request);

        return view('settings.kontab-billing', [
            'doctor' => $doctor,
            'enabled' => $doctor?->e_invoicing_enabled ?? false,
            'apiKeyId' => $doctor?->kontab_api_key_id,
            'hasSecret' => filled($doctor?->kontab_api_secret_encrypted),
        ]);
    }

    public function update(Request $request)
    {
        $doctor = $this->resolveDoctor($request);
        abort_if(! $doctor, 422, 'No hay un doctor asociado a este usuario.');

        $data = $request->validate([
            'enabled' => ['sometimes', 'boolean'],
            'kontab_api_key_id' => ['nullable', 'string', 'max:120'],
            'kontab_api_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $enabled = (bool) ($data['enabled'] ?? false);

        // Si está activando o cambiando la key, exigimos secret nuevo y validamos contra kontab-erp.
        if ($enabled || filled($data['kontab_api_key_id'])) {
            if (empty($data['kontab_api_key_id']) || empty($data['kontab_api_secret'])) {
                return back()->with('error', 'API Key ID y Secret son requeridos para activar la facturación electrónica.');
            }

            $doctor->kontab_api_key_id = $data['kontab_api_key_id'];
            $doctor->kontab_api_secret_encrypted = Crypt::encryptString($data['kontab_api_secret']);

            try {
                $client = new KontabClient($doctor);
                $ok = $client->testConnection();
            } catch (\Throwable $e) {
                return back()->with('error', 'No se pudo conectar a kontab-erp: '.$e->getMessage());
            }

            if (! $ok) {
                return back()->with('error', 'Las credenciales no fueron aceptadas por kontab-erp. Verifica el API Key ID y Secret.');
            }
        }

        $doctor->e_invoicing_enabled = $enabled;
        $doctor->save();

        return redirect()->route('settings.kontab-billing.edit')
            ->with('success', $enabled
                ? 'Facturación electrónica activa. Las nuevas facturas se enviarán a kontab-erp.'
                : 'Facturación electrónica desactivada. Volverás al flujo de NCF locales.');
    }

    public function test(Request $request)
    {
        $doctor = $this->resolveDoctor($request);
        abort_if(! $doctor, 422, 'No hay un doctor asociado a este usuario.');

        $data = $request->validate([
            'kontab_api_key_id' => ['required', 'string', 'max:120'],
            'kontab_api_secret' => ['required', 'string', 'max:255'],
        ]);

        // Test sin persistir: cargamos un Doctor temporal con las creds del request.
        $doctor->kontab_api_key_id = $data['kontab_api_key_id'];
        $doctor->kontab_api_secret_encrypted = Crypt::encryptString($data['kontab_api_secret']);

        try {
            $ok = (new KontabClient($doctor))->testConnection();

            return response()->json(['ok' => $ok, 'message' => $ok ? 'Conexión OK.' : 'Credenciales rechazadas por kontab-erp.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** Doctor del usuario actual; si es admin/analyst sin doctor, requiere ?doctor_id= en query. */
    private function resolveDoctor(Request $request): ?Doctor
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if (method_exists($user, 'doctor') && $user->doctor) {
            return $user->doctor;
        }

        $doctorId = (int) $request->query('doctor_id', $request->input('doctor_id', 0));
        if ($doctorId > 0) {
            return Doctor::find($doctorId);
        }

        return null;
    }
}
