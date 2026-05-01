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
        'paid_at',
        'kontab_invoice_id',
        'kontab_contact_id',
        'kontab_ncf',
        'kontab_track_id',
        'kontab_security_code',
        'kontab_dgii_status',
        'kontab_dgii_response_at',
        'kontab_dgii_response',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'total_amount' => 'decimal:2',
        'kontab_dgii_response_at' => 'datetime',
        'kontab_dgii_response' => 'array',
    ];

    /**
     * Devuelve true si la factura ya fue enviada a kontab-erp y NO fue rechazada.
     * En ese estado se bloquea edición/eliminación.
     */
    public function isLocked(): bool
    {
        return $this->kontab_invoice_id !== null
            && $this->kontab_dgii_status !== 'rejected';
    }

    /** Cache local del kontab_contact_id por (doctor, insurer). */
    public function insurerKontabContact()
    {
        return $this->hasOneThrough(
            \App\Models\InsurerKontabContact::class,
            \App\Models\Insurer::class,
            'id', 'insurer_id', 'insurer_id', 'id',
        );
    }

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
