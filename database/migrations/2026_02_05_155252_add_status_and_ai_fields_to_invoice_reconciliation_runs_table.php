<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoice_reconciliation_runs', function (Blueprint $table) {

            if (!Schema::hasColumn('invoice_reconciliation_runs', 'mode')) {
                $table->string('mode', 20)->nullable()->after('invoice_reconciliation_id');
                // ejemplo: automatic | manual
            }

            if (!Schema::hasColumn('invoice_reconciliation_runs', 'status')) {
                $table->string('status', 30)->nullable()->after('mode');
                // ejemplo: uploaded | prefilled | failed | ok
            }

            if (!Schema::hasColumn('invoice_reconciliation_runs', 'source_file')) {
                $table->string('source_file')->nullable()->after('status');
            }

            if (!Schema::hasColumn('invoice_reconciliation_runs', 'matched_count')) {
                $table->unsignedInteger('matched_count')->default(0)->after('source_file');
            }

            if (!Schema::hasColumn('invoice_reconciliation_runs', 'ai_result')) {
                $table->json('ai_result')->nullable()->after('raw_response');
            }

            if (!Schema::hasColumn('invoice_reconciliation_runs', 'error')) {
                $table->text('error')->nullable()->after('ai_result');
            }

            if (!Schema::hasColumn('invoice_reconciliation_runs', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_reconciliation_runs', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_reconciliation_runs', 'created_by')) $table->dropConstrainedForeignId('created_by');
            if (Schema::hasColumn('invoice_reconciliation_runs', 'error')) $table->dropColumn('error');
            if (Schema::hasColumn('invoice_reconciliation_runs', 'ai_result')) $table->dropColumn('ai_result');
            if (Schema::hasColumn('invoice_reconciliation_runs', 'matched_count')) $table->dropColumn('matched_count');
            if (Schema::hasColumn('invoice_reconciliation_runs', 'source_file')) $table->dropColumn('source_file');
            if (Schema::hasColumn('invoice_reconciliation_runs', 'status')) $table->dropColumn('status');
            if (Schema::hasColumn('invoice_reconciliation_runs', 'mode')) $table->dropColumn('mode');
        });
    }
};