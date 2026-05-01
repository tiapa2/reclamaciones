<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->boolean('e_invoicing_enabled')->default(false)->after('email');
            $table->string('kontab_api_key_id', 120)->nullable()->after('e_invoicing_enabled');
            $table->text('kontab_api_secret_encrypted')->nullable()->after('kontab_api_key_id');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['e_invoicing_enabled', 'kontab_api_key_id', 'kontab_api_secret_encrypted']);
        });
    }
};
