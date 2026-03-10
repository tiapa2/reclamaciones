<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total_amount');
            $table->enum('payment_status', ['unpaid','partial','paid'])->default('unpaid')->after('status');
            $table->timestamp('paid_at')->nullable()->after('payment_status');

            $table->index(['payment_status', 'invoice_date']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['payment_status', 'invoice_date']);
            $table->dropColumn(['paid_amount','payment_status','paid_at']);
        });
    }
};
