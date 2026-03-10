<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceReconciliationRun extends Model
{
    protected $fillable = [
        'invoice_reconciliation_id',

        // OpenAI / ejecución
        'mode',                 // automatic | manual
        'status',               // uploaded | prefilled | failed | ok
        'model',                // gpt-4.1, gpt-4o-mini, etc
        'openai_response_id',
        'prompt_hash',

        // Archivo origen
        'source_file',

        // Resultados IA
        'matched_count',
        'tokens',
        'raw_response',
        'ai_result',
        'error',

        // Auditoría
        'created_by',
    ];

    protected $casts = [
        'tokens'       => 'array',
        'raw_response' => 'array',
        'ai_result'    => 'array',
    ];

    /* ============================
     | Relaciones
     |============================ */

    public function reconciliation()
    {
        return $this->belongsTo(
            InvoiceReconciliation::class,
            'invoice_reconciliation_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ============================
     | Scopes útiles
     |============================ */

    public function scopeAutomatic($q)
    {
        return $q->where('mode', 'automatic');
    }

    public function scopeFailed($q)
    {
        return $q->where('status', 'failed');
    }

    public function scopeSuccessful($q)
    {
        return $q->where('status', 'prefilled');
    }
}