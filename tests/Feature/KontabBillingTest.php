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
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config(['services.kontab.base_url' => 'https://kontab.test/api/integration/v1']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);
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
    $auth = new DoctorNcfAuthorization([
        'doctor_id' => $doctor->id,
        'ncf_type_id' => $ncfType->id,
        'from_number' => 1,
        'to_number' => 100,
        'current_number' => 1,
        'active' => true,
    ]);
    $auth->insurer_id = $insurer->id; // no está en $fillable
    $auth->save();

    $this->actingAs($admin)->post('/invoices', [
        'doctor_id' => $doctor->id,
        'insurer_id' => $insurer->id,
        'ncf_type_id' => $ncfType->id,
        'invoice_date' => now()->toDateString(),
        'invoice_type' => 'ars',
        'items' => [['service_date' => now()->toDateString(), 'patient_name' => 'Juan', 'authorization_no' => 'AUTH-1', 'amount' => 1000]],
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
        'kontab.test/api/integration/v1/lookups/ncf-types' => Http::response([
            'data' => [
                ['id' => 41, 'code' => 'E32', 'name' => 'Consumo Electrónico'],
                ['id' => 42, 'code' => 'E31', 'name' => 'Crédito Fiscal Electrónico'],
            ],
        ], 200),
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
        'items' => [['service_date' => now()->toDateString(), 'patient_name' => 'Ana', 'authorization_no' => 'AUTH-2', 'amount' => 5000]],
    ]);

    $invoice = Invoice::latest()->first();
    expect($invoice->kontab_invoice_id)->toBe(9999);
    expect($invoice->kontab_ncf)->toBe('E310000000123');
    expect($invoice->kontab_dgii_status)->toBe('pending');
    expect($invoice->isLocked())->toBeTrue();

    // Debe resolver el ncf_type_id de kontab (E31 → 42) y enviarlo en el payload.
    Http::assertSent(function ($request) {
        return str_ends_with($request->url(), '/invoices/sales')
            && $request['ncf_type_id'] === 42;
    });

    // Intentar destroy → 403
    $this->actingAs($admin)->delete("/invoices/{$invoice->id}")
        ->assertStatus(403);
});

it('webhook DGII accepted actualiza el status', function () {
    config(['app.cipher' => 'aes-256-cbc']);
    config(['services.kontab.webhook_secret' => 'test-webhook-secret']);

    $doctor = Doctor::create(['rnc' => '5', 'full_name' => 'X', 'email' => 'x@x.com', 'e_invoicing_enabled' => true,
        'kontab_api_key_id' => 'k', 'kontab_api_secret_encrypted' => Crypt::encryptString('s')]);
    $insurer = Insurer::create(['name' => 'ARS', 'rnc' => '6']);
    $user = User::factory()->create();
    $ncfType = NcfType::create(['name' => 'E31', 'prefix' => 'E31', 'active' => true]);
    $invoice = Invoice::create([
        'doctor_id' => $doctor->id, 'insurer_id' => $insurer->id, 'ncf_type_id' => $ncfType->id,
        'invoice_date' => now(), 'total_amount' => 1000, 'status' => 'issued',
        'created_by' => $user->id,
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
    config(['services.kontab.webhook_secret' => 'test-webhook-secret']);

    $this->postJson('/webhooks/kontab', ['event' => 'x', 'data' => ['invoice_id' => 1]], [
        'X-Kontab-Event' => 'invoice.dgii.accepted',
        'X-Kontab-Timestamp' => (string) time(),
        'X-Kontab-Signature' => 'sha256=deadbeef',
    ])->assertStatus(401);
});

it('admin actualiza doctor con credenciales kontab desde el modal', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage doctors', 'guard_name' => 'web']));

    $doctor = Doctor::create([
        'rnc' => '999888777',
        'full_name' => 'Dr Existente',
        'email' => 'doc@test.com',
        'e_invoicing_enabled' => false,
    ]);

    $this->actingAs($admin)->patch("/doctors/{$doctor->id}", [
        'rnc' => '999888777',
        'full_name' => 'Dr Existente',
        'e_invoicing_enabled' => '1',
        'kontab_api_key_id' => 'kt_admin_key',
        'kontab_api_secret' => 'sk_admin_secret',
    ])->assertRedirect();

    $doctor->refresh();
    expect($doctor->e_invoicing_enabled)->toBeTrue();
    expect($doctor->kontab_api_key_id)->toBe('kt_admin_key');
    expect(Crypt::decryptString($doctor->kontab_api_secret_encrypted))->toBe('sk_admin_secret');
});

it('testConnection firma el REQUEST_URI completo y pega a /ping', function () {
    Http::fake([
        'kontab.test/api/integration/v1/ping' => Http::response(['success' => true, 'data' => ['pong' => true]], 200),
    ]);

    $secret = 'sk_test_secret';
    $doctor = Doctor::create([
        'rnc' => '777666555',
        'full_name' => 'Dr Ping',
        'email' => 'ping@test.com',
        'e_invoicing_enabled' => true,
        'kontab_api_key_id' => 'kt_test_key',
        'kontab_api_secret_encrypted' => Crypt::encryptString($secret),
    ]);

    $ok = (new KontabClient($doctor))->testConnection();
    expect($ok)->toBeTrue();

    Http::assertSent(function ($request) use ($secret) {
        // Debe pegar al endpoint /ping con el prefijo completo
        if ($request->url() !== 'https://kontab.test/api/integration/v1/ping') {
            return false;
        }
        // La firma debe cubrir el REQUEST_URI completo (con /api/integration/v1),
        // no el path relativo — es exactamente lo que valida kontab-erp.
        $ts = $request->header('X-Api-Timestamp')[0];
        $expected = hash_hmac(
            'sha256',
            "{$ts}\nGET\n/api/integration/v1/ping\n".hash('sha256', ''),
            $secret,
        );

        return $request->header('X-Api-Signature')[0] === $expected
            && $request->header('X-Api-Key')[0] === 'kt_test_key';
    });
});

it('mantiene el secret existente si el admin guarda con campo secret vacío', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $original = Crypt::encryptString('original_secret');
    $doctor = Doctor::create([
        'rnc' => '111222333',
        'full_name' => 'Dr Persistente',
        'email' => 'persist@test.com',
        'e_invoicing_enabled' => true,
        'kontab_api_key_id' => 'kt_old',
        'kontab_api_secret_encrypted' => $original,
    ]);

    $this->actingAs($admin)->patch("/doctors/{$doctor->id}", [
        'rnc' => '111222333',
        'full_name' => 'Dr Persistente',
        'e_invoicing_enabled' => '1',
        'kontab_api_key_id' => 'kt_new', // cambia key
        // secret vacío → mantener
    ])->assertRedirect();

    $doctor->refresh();
    expect($doctor->kontab_api_key_id)->toBe('kt_new');
    expect(Crypt::decryptString($doctor->kontab_api_secret_encrypted))->toBe('original_secret');
});
