<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'doctor_id',
        'insurer_id',
        'ncf_type_id',
        'invoice_date',
        'ncf_number',
        'ncf_seq',
        'total_amount',
        'invoice_type',
        'status',
        'created_by',
        'paid_amount',
        'payment_status',
        'paid_at'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    // Relaciones
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }

    public function ncfType()
    {
        return $this->belongsTo(NcfType::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
{
    return $this->hasMany(InvoicePayment::class);
}

public function getBalanceAttribute()
{
    $total = (float) $this->total_amount;
    $paid  = (float) $this->paid_amount;
    return max(0, $total - $paid);
}

public function factoring()
{
    return $this->hasOne(\App\Models\Factoring::class);
}

public function reconciliations()
{
    return $this->hasMany(\App\Models\InvoiceReconciliation::class);
}

public function openReconciliation()
{
    return $this->hasOne(\App\Models\InvoiceReconciliation::class)->where('status', 'open');
}


}
