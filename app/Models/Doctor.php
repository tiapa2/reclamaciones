<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rnc',
        'full_name',
        'specialty',
        'institution',
        'phone',
        'email',
        'e_invoicing_enabled',
        'kontab_api_key_id',
        'kontab_api_secret_encrypted',
    ];

    protected $casts = [
        'e_invoicing_enabled' => 'boolean',
    ];

    protected $hidden = [
        'kontab_api_secret_encrypted',
    ];

    public function insurers()
    {
        return $this->belongsToMany(Insurer::class, 'doctor_insurer')
            ->withPivot(['doctor_code'])
            ->withTimestamps();
    }

    public function ncfAuthorizations()
    {
        return $this->hasMany(DoctorNcfAuthorization::class);
    }
    public function invoices()
{
    return $this->hasMany(\App\Models\Invoice::class);
}
// App\Models\Doctor.php
public function user()
{
    return $this->belongsTo(\App\Models\User::class);
}


}
