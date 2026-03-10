<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factoring extends Model
{
    protected $fillable = [
        'invoice_id',
        'doctor_id',
        'insurer_id',
        'created_by',
        'purchase_date',
        'term_days',
        'due_date',
        'invoice_total',
        'discount_rate',
        'commission_rate',
        'discount_amount',
        'commission_amount',
        'net_to_doctor',
        'expected_profit',
        'status',
        'collected_at',
        'reference_no',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',
        'collected_at' => 'date',
        'invoice_total' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_to_doctor' => 'decimal:2',
        'expected_profit' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
