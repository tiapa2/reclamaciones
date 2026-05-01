<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('kontab_invoice_id')->nullable()->after('payment_status');
            $table->unsignedBigInteger('kontab_contact_id')->nullable()->after('kontab_invoice_id');
            $table->string('kontab_ncf', 20)->nullable()->after('kontab_contact_id');
            $table->string('kontab_track_id', 80)->nullable()->after('kontab_ncf');
            $table->string('kontab_security_code', 20)->nullable()->after('kontab_track_id');
            $table->enum('kontab_dgii_status', ['pending', 'accepted', 'rejected'])->nullable()->after('kontab_security_code');
            $table->timestamp('kontab_dgii_response_at')->nullable()->after('kontab_dgii_status');
            $table->json('kontab_dgii_response')->nullable()->after('kontab_dgii_response_at');

            $table->index('kontab_invoice_id');
            $table->index('kontab_dgii_status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['kontab_invoice_id']);
            $table->dropIndex(['kontab_dgii_status']);
            $table->dropColumn([
                'kontab_invoice_id', 'kontab_contact_id', 'kontab_ncf',
                'kontab_track_id', 'kontab_security_code', 'kontab_dgii_status',
                'kontab_dgii_response_at', 'kontab_dgii_response',
            ]);
        });
    }
};
