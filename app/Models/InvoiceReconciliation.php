<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceReconciliation extends Model
{
    protected $fillable = [
        'invoice_id',
        'doctor_id',
        'insurer_id',
        'type',
        'status',
        'started_at',
        'closed_at',
        'source_file_path',
        'source_meta',
        'reference_no',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'date',
        'closed_at' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceReconciliationItem::class, 'invoice_reconciliation_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }
    public function runs()
    {
        return $this->hasMany(InvoiceReconciliationRun::class);
    }

    public function latestRun()
    {
        return $this->hasOne(InvoiceReconciliationRun::class)->latestOfMany();
    }
}
