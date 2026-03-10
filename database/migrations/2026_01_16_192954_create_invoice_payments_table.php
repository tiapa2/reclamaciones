<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_invoice_payments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->date('payment_date');
            $table->decimal('amount', 12, 2);

            $table->enum('method', ['cash','transfer','card','check','other'])->default('transfer');
            $table->string('reference_no', 80)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();

            $table->index(['invoice_id','payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
