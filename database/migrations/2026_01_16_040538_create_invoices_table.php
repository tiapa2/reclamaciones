<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurer_id')->constrained()->cascadeOnDelete(); // ARS
            $table->foreignId('ncf_type_id')->constrained('ncf_types');

            $table->date('invoice_date');

            // NCF completo (Ej: B0100000124). Único.
            $table->string('ncf_number', 30)->unique();

            // Secuencia numérica (Ej: 124) para control interno
            $table->unsignedBigInteger('ncf_seq');

            $table->decimal('total_amount', 12, 2)->default(0);

            $table->enum('status', ['draft', 'issued', 'void'])->default('issued');

            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();

            // Índices útiles
            $table->index(['doctor_id', 'invoice_date']);
            $table->index(['insurer_id', 'invoice_date']);
            $table->index(['ncf_type_id', 'invoice_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
