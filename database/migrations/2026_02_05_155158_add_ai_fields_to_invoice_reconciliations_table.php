<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoice_reconciliations', function (Blueprint $table) {
            // Manual vs automatic
            if (!Schema::hasColumn('invoice_reconciliations', 'type')) {
                $table->string('type', 20)->default('manual')->after('status');
                // valores esperados: manual | automatic
            }

            if (!Schema::hasColumn('invoice_reconciliations', 'source_file_path')) {
                $table->string('source_file_path')->nullable()->after('type');
            }

            if (!Schema::hasColumn('invoice_reconciliations', 'source_meta')) {
                $table->json('source_meta')->nullable()->after('source_file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_reconciliations', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_reconciliations', 'source_meta')) $table->dropColumn('source_meta');
            if (Schema::hasColumn('invoice_reconciliations', 'source_file_path')) $table->dropColumn('source_file_path');
            if (Schema::hasColumn('invoice_reconciliations', 'type')) $table->dropColumn('type');
        });
    }
};