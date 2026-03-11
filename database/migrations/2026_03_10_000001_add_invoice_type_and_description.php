<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_type', 20)->default('ars')->after('invoice_date');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('description', 500)->nullable()->after('service_date');
            $table->string('patient_name', 255)->nullable()->change();
            $table->string('affiliate_no', 80)->nullable()->change();
            $table->string('authorization_no', 80)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_type');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->string('patient_name', 255)->nullable(false)->change();
            $table->string('affiliate_no', 80)->nullable(false)->change();
            $table->string('authorization_no', 80)->nullable(false)->change();
        });
    }
};
