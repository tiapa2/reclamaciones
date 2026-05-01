<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache local del kontab_contact_id por (doctor, insurer).
 * Cada doctor puede tener su propia credencial en kontab-erp,
 * y cada credencial registra los contactos en su company; por eso
 * la pareja (doctor, insurer) es la clave única.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurer_kontab_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('insurer_id')->constrained('insurers')->cascadeOnDelete();
            $table->unsignedBigInteger('kontab_contact_id');
            $table->timestamps();

            $table->unique(['doctor_id', 'insurer_id']);
            $table->index('kontab_contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurer_kontab_contacts');
    }
};
