<?php

use App\Models\Doctor;
use App\Models\DoctorNcfAuthorization;
use App\Models\Insurer;
use App\Models\Invoice;
use App\Models\NcfType;
use App\Models\User;
use App\Services\KontabClient;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.kontab.base_url' => 'https://kontab.test/api/integration/v1']);
    config(['app.env' => 'testing']);
});

it('genera NCF local cuando el doctor no tiene facturación electrónica activa', function () {
    Http::fake(); // Si llama a kontab por error, registramos.

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $doctor = Doctor::create([
        'rnc' => '111111111',
        'full_name' => 'Dr Local',
        'email' => 'local@test.com',
        'e_invoicing_enabled' => false,
    ]);
    $insurer = Insurer::create(['name' => 'ARS Test', 'rnc' => '222222222']);
    $ncfType = NcfType::create(['name' => 'B01', 'prefix' => 'B01', 'active' => true]);
    DoctorNcfAuthorization::create([
        'doctor_id' => $doctor->id,
        'ncf_type_id' => $ncfType->id,
        'from_number' => 1,
        'to_number' => 100,
        'current_number' => 1,
        'active' => true,
    ]);

    $this->actingAs($admin)->post('/invoices', [
        'doctor_id' => $doctor->id,
        'insurer_id' => $insurer->id,
        'ncf_type_id' => $ncfType->id,
        'invoice_date' => now()->toDateString(),
        'invoice_type' => 'ars',
        'items' => [['service_date' => now()->toDateString(), 'patient_name' => 'Juan', 'amount' => 1000]],
    ]);

    $invoice = Invoice::latest()->first();
    expect($invoice->ncf_number)->toBe('B0100000001');
    expect($invoice->kontab_invoice_id)->toBeNull();
    expect($invoice->isLocked())->toBeFalse();

    Http::assertNothingSent();
});

it('envía a kontab-erp cuando el doctor tiene facturación electrónica y bloquea edición', function () {
    Http::fake([
        'kontab.test/api/integration/v1/contacts' => Http::response(['id' => 555], 201),
        'kontab.test/api/integration/v1/invoices/sales' => Http::response([
            'id' => 9999,
            'ncf' => 'E310000000123',
            'track_id' => 'TRK-ABC',
            'security_code' => 'SEC-XYZ',
            'dgii_status' => 'pending',
        ], 201),
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $doctor = Doctor::create([
        'rnc' => '333333333',
        'full_name' => 'Dr Electrónico',
        'email' => 'elec@test.com',
        'e_invoicing_enabled' => true,
        'kontab_api_key_id' => 'kt_test_key',
        'kontab_api_secret_encrypted' => Crypt::encryptString('sk_test_secret'),
    ]);
    $insurer = Insurer::create(['name' => 'ARS E', 'rnc' => '444444444']);
    $ncfType = NcfType::create(['name' => 'E31', 'prefix' => 'E31', 'active' => true]);

    $this->actingAs($admin)->post('/invoices', [
        'doctor_id' => $doctor->id,
        'insurer_id' => $insurer->id,
        'ncf_type_id' => $ncfType->id,
        'invoice_date' => now()->toDateString(),
        'invoice_type' => 'ars',
        'items' => [['service_date' => now()->toDateString(), 'patient_name' => 'Ana', 'amount' => 5000]],
    ]);

    $invoice = Invoice::latest()->first();
    expect($invoice->kontab_invoice_id)->toBe(9999);
    expect($invoice->kontab_ncf)->toBe('E310000000123');
    expect($invoice->kontab_dgii_status)->toBe('pending');
    expect($invoice->isLocked())->toBeTrue();

    // Intentar destroy → 403
    $this->actingAs($admin)->delete("/invoices/{$invoice->id}")
        ->assertStatus(403);
});

it('webhook DGII accepted actualiza el status', function () {
    config(['app.cipher' => 'aes-256-cbc']);
    putenv('KONTAB_WEBHOOK_SECRET=test-webhook-secret');

    $doctor = Doctor::create(['rnc' => '5', 'full_name' => 'X', 'email' => 'x@x.com', 'e_invoicing_enabled' => true,
        'kontab_api_key_id' => 'k', 'kontab_api_secret_encrypted' => Crypt::encryptString('s')]);
    $insurer = Insurer::create(['name' => 'ARS', 'rnc' => '6']);
    $invoice = Invoice::create([
        'doctor_id' => $doctor->id, 'insurer_id' => $insurer->id, 'ncf_type_id' => 1,
        'invoice_date' => now(), 'total_amount' => 1000, 'status' => 'issued',
        'kontab_invoice_id' => 7777, 'kontab_dgii_status' => 'pending',
    ]);

    $body = json_encode([
        'event' => 'invoice.dgii.accepted',
        'data' => ['invoice_id' => 7777, 'ncf' => 'E310000007777', 'track_id' => 'CONFIRMED'],
    ]);
    $ts = (string) time();
    $sig = 'sha256='.hash_hmac('sha256', $ts.'.'.$body, 'test-webhook-secret');

    $this->postJson('/webhooks/kontab', json_decode($body, true), [
        'X-Kontab-Event' => 'invoice.dgii.accepted',
        'X-Kontab-Timestamp' => $ts,
        'X-Kontab-Signature' => $sig,
    ])->assertOk();

    $invoice->refresh();
    expect($invoice->kontab_dgii_status)->toBe('accepted');
    expect($invoice->kontab_track_id)->toBe('CONFIRMED');
});

it('rechaza webhook con firma inválida', function () {
    putenv('KONTAB_WEBHOOK_SECRET=test-webhook-secret');

    $this->postJson('/webhooks/kontab', ['event' => 'x', 'data' => ['invoice_id' => 1]], [
        'X-Kontab-Event' => 'invoice.dgii.accepted',
        'X-Kontab-Timestamp' => (string) time(),
        'X-Kontab-Signature' => 'sha256=deadbeef',
    ])->assertStatus(401);
});
