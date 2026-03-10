<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_reconciliations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('insurer_id')->nullable()->constrained('insurers')->nullOnDelete();

            $table->string('status', 20)->default('open'); // open|closed|canceled
            $table->date('started_at')->nullable();
            $table->date('closed_at')->nullable();

            $table->string('reference_no')->nullable(); // lote/volante/archivo seguro
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index(['doctor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reconciliations');
    }
};
