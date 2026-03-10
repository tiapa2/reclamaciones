<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Insurer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'rnc',
        'email',
        'phone',
    ];

    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_insurer')
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


}
