<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceReconciliationItem extends Model
{
    protected $fillable = [
        'invoice_reconciliation_id',
        'invoice_item_id',
        'service_date',
        'patient_name',
        'affiliate_no',
        'authorization_no',
        'amount_billed',
        'amount_paid',
        'adjustment_amount',
        'balance',
        'status',
        'paid_at',
        'claim_no',
        'reason_code',
        'notes',
        'match_confidence',
        'raw'
    ];

    protected $casts = [
        'service_date' => 'date',
        'paid_at' => 'date',
        'amount_billed' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(InvoiceReconciliation::class, 'invoice_reconciliation_id');
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }
}
