<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            $table->date('service_date');

            $table->string('patient_name', 255);
            $table->string('affiliate_no', 80);
            $table->string('authorization_no', 80);

            // Si tienes catálogo de procedimientos luego lo volvemos FK
            $table->unsignedBigInteger('procedure_id')->nullable();

            $table->decimal('amount', 12, 2);

            $table->timestamps();

            $table->index(['invoice_id', 'service_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
