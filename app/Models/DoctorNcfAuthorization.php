<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorNcfAuthorization extends Model
{
    

    protected $fillable = [
        'doctor_id','ncf_type_id',
        'from_number','to_number','current_number',
        'requested_at','expires_at','active'
    ];

    protected $casts = [
        'requested_at' => 'date',
        'expires_at' => 'date',
        'active' => 'boolean'
    ];

    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function ncfType() { return $this->belongsTo(NcfType::class); }
}

