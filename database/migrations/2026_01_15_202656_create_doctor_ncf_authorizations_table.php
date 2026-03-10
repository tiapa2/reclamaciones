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
        Schema::create('doctor_ncf_authorizations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ncf_type_id')->constrained('ncf_types')->cascadeOnDelete();

            $table->unsignedBigInteger('from_number'); // ej 124 (si guardas solo el correlativo)
            $table->unsignedBigInteger('to_number');   // ej 147
            $table->unsignedBigInteger('current_number')->nullable(); // último usado (opcional)

            $table->date('requested_at')->nullable();
            $table->date('expires_at')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();

            // 1 autorización activa por doctor+ars+tipo (si quieres permitir varias, quitas esto)
            $table->unique(['doctor_id', 'insurer_id', 'ncf_type_id'], 'uniq_doctor_insurer_ncf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_ncf_authorizations');
    }
};
