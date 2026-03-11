<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'service_date',
        'description',
        'patient_name',
        'affiliate_no',
        'authorization_no',
        'procedure_id',
        'amount',
    ];

    protected $casts = [
        'service_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
