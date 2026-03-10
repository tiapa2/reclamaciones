<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_reconciliation_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_reconciliation_id')
                ->constrained('invoice_reconciliations')
                ->cascadeOnDelete();

            $table->string('model')->nullable();
            $table->string('openai_response_id')->nullable();
            $table->string('prompt_hash')->nullable();

            $table->json('tokens')->nullable();
            $table->json('raw_response')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reconciliation_runs');
    }
};
