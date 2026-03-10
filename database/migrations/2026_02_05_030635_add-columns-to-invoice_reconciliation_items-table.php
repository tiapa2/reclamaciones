<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoice_reconciliation_items', function (Blueprint $table) {
            $table->decimal('match_confidence', 5, 2)->nullable(); // 0..100
            $table->json('raw')->nullable(); // línea cruda de la IA
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_reconciliation_items', function (Blueprint $table) {
            $table->dropColumn(['match_confidence','raw']);
        });
    }
};
