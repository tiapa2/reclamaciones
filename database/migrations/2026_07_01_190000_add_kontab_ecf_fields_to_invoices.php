<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos de representación fiscal del e-CF (traídos de kontab-erp) para el PDF:
 * QR (SVG base64), su URL de ConsultaTimbre y la fecha/hora de firma.
 * El código de seguridad ya vive en kontab_security_code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->longText('kontab_qr_svg')->nullable()->after('kontab_security_code');
            $table->text('kontab_qr_url')->nullable()->after('kontab_qr_svg');
            $table->string('kontab_fecha_firma')->nullable()->after('kontab_qr_url');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['kontab_qr_svg', 'kontab_qr_url', 'kontab_fecha_firma']);
        });
    }
};
