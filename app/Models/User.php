<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// ✅ IMPORTANTE
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function doctor()
    {
        return $this->hasOne(\App\Models\Doctor::class);
    }

    public function createdInvoices()
    {
        return $this->hasMany(\App\Models\Invoice::class, 'created_by');
    }

    public function createdPayments()
    {
        return $this->hasMany(\App\Models\InvoicePayment::class, 'created_by');
    }

    public function createdFactorings()
    {
        return $this->hasMany(\App\Models\Factoring::class, 'created_by');
    }
}
