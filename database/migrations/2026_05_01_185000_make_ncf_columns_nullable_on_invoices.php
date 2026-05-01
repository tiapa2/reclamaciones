<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuando una factura se emite vía facturación electrónica (kontab-erp),
 * el NCF lo asigna kontab y llega DESPUÉS del create. Permitimos null
 * temporalmente; el flujo local sigue setteando ncf_number/seq en el create.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('ncf_number')->nullable()->change();
            $table->unsignedBigInteger('ncf_seq')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No-op: revertir podría romper filas con NCF de kontab pendiente.
    }
};
