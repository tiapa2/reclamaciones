<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_reconciliations', function (Blueprint $table) {
            $table->enum('type', ['manual', 'auto'])->default('manual');
            $table->string('source_file_path')->nullable();
            $table->json('source_meta')->nullable(); // solicitud, neto, deducciones, etc.
           
        });
    }

    public function down(): void
    {
        Schema::table('invoice_reconciliations', function (Blueprint $table) {
            $table->dropColumn(['type','source_file_path','source_meta']);
        });
    }
};
