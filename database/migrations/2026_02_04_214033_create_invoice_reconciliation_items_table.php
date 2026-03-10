<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_reconciliation_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_reconciliation_id')
                ->constrained('invoice_reconciliations')
                ->cascadeOnDelete();

            $table->foreignId('invoice_item_id')
                ->nullable()
                ->constrained('invoice_items')
                ->nullOnDelete();

            // Snapshot (no se debe alterar con cambios futuros)
            $table->date('service_date')->nullable();
            $table->string('patient_name')->nullable();
            $table->string('affiliate_no')->nullable();
            $table->string('authorization_no')->nullable(); // reclamo/autorización
            $table->decimal('amount_billed', 12, 2)->default(0);

            // Conciliación
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('adjustment_amount', 12, 2)->default(0); // descuentos/ajustes
            $table->decimal('balance', 12, 2)->default(0);

            $table->string('status', 20)->default('pending'); // pending|partial|paid|rejected|adjusted
            $table->date('paid_at')->nullable();

            $table->string('claim_no')->nullable(); // a veces “reclamación” difiere
            $table->string('reason_code')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['invoice_reconciliation_id']);
            $table->index(['authorization_no']);
            $table->index(['claim_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reconciliation_items');
    }
};
