<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Retención de ISR (honorarios) que la ARS/clínica retiene al médico.
            // pct = tasa capturada; amount = monto calculado sobre el total (exento de ITBIS).
            $table->decimal('isr_retention_pct', 5, 2)->nullable()->after('total_amount');
            $table->decimal('isr_retention_amount', 12, 2)->default(0)->after('isr_retention_pct');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['isr_retention_pct', 'isr_retention_amount']);
        });
    }
};
