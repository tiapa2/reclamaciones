<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();

            $table->string('rnc', 20)->unique(); // puede ser cédula o RNC
            $table->string('full_name', 150);
            $table->string('specialty', 120)->nullable();
            $table->string('institution', 150)->nullable();

            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
