<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha de vencimiento de la secuencia e-NCF (traída de kontab-erp) para
 * mostrar "Válido Hasta" en el comprobante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('kontab_valid_until')->nullable()->after('kontab_fecha_firma');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('kontab_valid_until');
        });
    }
};
