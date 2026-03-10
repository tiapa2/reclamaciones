<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factorings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('purchase_date');
            $table->unsignedInteger('term_days')->default(30);
            $table->date('due_date');

            // Snapshot del total facturado en el momento de compra
            $table->decimal('invoice_total', 12, 2);

            // Parámetros
            $table->decimal('discount_rate', 6, 2)->default(0);     // %
            $table->decimal('commission_rate', 6, 2)->default(0);   // %
            $table->unsignedTinyInteger('term_basis')->default(30); // opcional: 30/45/60 si luego te sirve

            // Montos calculados (snapshot)
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('net_to_doctor', 12, 2)->default(0);
            $table->decimal('expected_profit', 12, 2)->default(0);

            $table->enum('status', ['active','collected','cancelled'])->default('active');
            $table->date('collected_at')->nullable();

            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // MVP: 1 factoring por factura
            $table->unique('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factorings');
    }
};
