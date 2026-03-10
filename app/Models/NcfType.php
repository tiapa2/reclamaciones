<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NcfType extends Model
{
    protected $table = 'ncf_types';

    protected $fillable = [
        'name',
        'prefix',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /* ========================
     |  Relaciones
     |======================== */

    public function doctorAuthorizations()
    {
        return $this->hasMany(DoctorNcfAuthorization::class);
    }
    public function invoices()
{
    return $this->hasMany(\App\Models\Invoice::class);
}

}
