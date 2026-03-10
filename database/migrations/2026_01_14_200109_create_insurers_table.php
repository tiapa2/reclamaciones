<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('rnc', 20)->unique();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();

            // Si más adelante manejas tipo comprobante por defecto:
            // $table->foreignId('default_ncf_type_id')->nullable()->constrained('ncf_types');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurers');
    }
};
