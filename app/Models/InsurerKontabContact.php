<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cache (doctor, insurer) → kontab_contact_id, para no recrear el contacto
 * en kontab-erp en cada factura.
 */
class InsurerKontabContact extends Model
{
    protected $table = 'insurer_kontab_contacts';

    protected $fillable = [
        'doctor_id', 'insurer_id', 'kontab_contact_id',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }
}
