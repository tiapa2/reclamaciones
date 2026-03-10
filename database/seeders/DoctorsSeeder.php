<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Insurer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorsSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/doctors.json');
        if (!file_exists($path)) {
            throw new \RuntimeException("No existe el archivo: {$path}");
        }

        $raw = file_get_contents($path);
        $json = json_decode($raw, true);

        if (!is_array($json)) {
            throw new \RuntimeException("El JSON no es válido.");
        }

        // Soporta JSON tipo: { status, data: [...] } o directamente [ ... ]
        $items = $json['data'] ?? $json;
        if (!is_array($items)) {
            throw new \RuntimeException("No se encontró el arreglo de doctores en 'data' ni como raíz.");
        }

        // Cache de insurers por id y por nombre (trim + lower)
        $insurersById = Insurer::query()->get()->keyBy('id');
        $insurersByName = Insurer::query()->get()->keyBy(function ($i) {
            return $this->normalizeName($i->name ?? '');
        });

        $pivotRows = [];

        DB::transaction(function () use ($items, $insurersById, $insurersByName, &$pivotRows) {

            foreach ($items as $row) {
                $rnc  = trim((string)($row['f_rnc_medico'] ?? ''));
                if ($rnc === '') {
                    continue;
                }

                $doctorData = [
                    'rnc'         => $rnc,
                    'full_name'   => trim((string)($row['f_nombre_completo'] ?? '')),
                    'specialty'   => trim((string)($row['f_especialidad'] ?? '')),
                    'institution' => trim((string)($row['f_institucion'] ?? '')),
                    'phone'       => trim((string)($row['f_telefono'] ?? '')),
                    'email'       => $this->toLowerOrNull($row['f_email'] ?? null),
                ];

                // Upsert del doctor por RNC (requiere idealmente unique index en doctors.rnc)
                $doctor = Doctor::withTrashed()->where('rnc', $rnc)->first();
                if ($doctor) {
                    // Si estaba borrado (soft delete), lo revivimos
                    if ($doctor->trashed()) {
                        $doctor->restore();
                    }
                    $doctor->fill($doctorData)->save();
                } else {
                    $doctor = Doctor::create($doctorData);
                }

                // Pivot: relacionesARS (si existe)
                $rels = $row['relacionesARS'] ?? [];
                if (!is_array($rels)) {
                    continue;
                }

                foreach ($rels as $rel) {
                    $insurerIdFromJson = $rel['f_id_ars'] ?? null;
                    $doctorCode = $rel['f_codigo_medico'] ?? null;

                    $insurer = null;

                    // 1) buscar por ID
                    if ($insurerIdFromJson !== null && isset($insurersById[$insurerIdFromJson])) {
                        $insurer = $insurersById[$insurerIdFromJson];
                    }

                    // 2) fallback: buscar por nombre si viene en rel['ars']['f_nombre_completo']
                    if (!$insurer) {
                        $nameFromJson = $rel['ars']['f_nombre_completo'] ?? null;
                        if ($nameFromJson) {
                            $key = $this->normalizeName($nameFromJson);
                            $insurer = $insurersByName[$key] ?? null;
                        }
                    }

                    // si no matchea insurer, lo saltamos (mejor que insertar basura)
                    if (!$insurer) {
                        continue;
                    }

                    $pivotRows[] = [
                        'doctor_id'   => $doctor->id,
                        'insurer_id'  => $insurer->id,
                        'doctor_code' => $doctorCode !== null ? (string)$doctorCode : null,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }
            }

            // Upsert masivo del pivot (respeta unique doctor_id+insurer_id)
            if (!empty($pivotRows)) {
                DB::table('doctor_insurer')->upsert(
                    $pivotRows,
                    ['doctor_id', 'insurer_id'],
                    ['doctor_code', 'updated_at']
                );
            }
        });
    }

    private function toLowerOrNull($value): ?string
    {
        $v = trim((string)($value ?? ''));
        return $v === '' ? null : mb_strtolower($v);
    }

    private function normalizeName(string $name): string
    {
        $n = mb_strtolower(trim($name));
        // colapsar espacios múltiples
        $n = preg_replace('/\s+/', ' ', $n);
        return $n ?? '';
    }
}
