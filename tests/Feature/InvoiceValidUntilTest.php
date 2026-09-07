<?php

use App\Models\Doctor;
use App\Models\DoctorNcfAuthorization;
use App\Models\Insurer;
use App\Models\Invoice;
use App\Models\NcfType;
use App\Models\User;

/**
 * El PDF traía "Válido Hasta: -" fijo (B1500000326 del Dr. Ivanhoe Balbuena,
 * 02/09/2026) aunque la autorización NCF del médico ya tenía su expires_at
 * cargada en el sistema.
 */
function invoiceWithAuthorization(array $authOverrides = [], array $invoiceOverrides = []): Invoice
{
    $doctor = Doctor::create([
        'rnc' => '071003960',
        'full_name' => 'Dr Ivanhoe Balbuena',
        'email' => 'ivanhoe@test.com',
        'e_invoicing_enabled' => false,
    ]);
    $insurer = Insurer::create(['name' => 'ARS SEMMA', 'rnc' => '401052662']);
    $type = NcfType::create(['name' => 'B15', 'prefix' => 'B15', 'active' => true]);

    $auth = new DoctorNcfAuthorization(array_merge([
        'doctor_id' => $doctor->id,
        'ncf_type_id' => $type->id,
        'from_number' => 300,
        'to_number' => 400,
        'current_number' => 327,
        'expires_at' => '2027-12-31',
        'active' => true,
    ], $authOverrides));
    $auth->insurer_id = $insurer->id; // no está en $fillable
    $auth->save();

    return Invoice::create(array_merge([
        'doctor_id' => $doctor->id,
        'insurer_id' => $insurer->id,
        'ncf_type_id' => $type->id,
        'invoice_date' => '2026-09-02',
        'invoice_type' => 'ars',
        'ncf_seq' => 326,
        'ncf_number' => 'B1500000326',
        'total_amount' => 1000,
        'status' => 'issued',
        'created_by' => User::factory()->create()->id,
    ], $invoiceOverrides));
}

it('muestra la fecha de vencimiento de la autorización NCF del médico', function () {
    expect(invoiceWithAuthorization()->validUntil())->toBe('31/12/2027');
});

it('usa la autorización cuyo rango cubre el número emitido', function () {
    $invoice = invoiceWithAuthorization();

    // Otra aseguradora del mismo médico con otro rango y otra fecha: no debe
    // colarse en el comprobante de esta factura.
    $otra = Insurer::create(['name' => 'ARS Otra', 'rnc' => '401052663']);
    $auth = new DoctorNcfAuthorization([
        'doctor_id' => $invoice->doctor_id,
        'ncf_type_id' => $invoice->ncf_type_id,
        'from_number' => 401,
        'to_number' => 500,
        'current_number' => 401,
        'expires_at' => '2029-12-31',
        'active' => true,
    ]);
    $auth->insurer_id = $otra->id;
    $auth->save();

    expect($invoice->fresh()->validUntil())->toBe('31/12/2027');
});

it('prefiere la fecha que manda kontab-erp en las facturas electrónicas', function () {
    $invoice = invoiceWithAuthorization([], ['kontab_valid_until' => '2028-12-31']);

    expect($invoice->validUntil())->toBe('31/12/2028');
});

it('devuelve null si el médico no tiene autorización con fecha', function () {
    $invoice = invoiceWithAuthorization(['expires_at' => null]);

    expect($invoice->validUntil())->toBeNull();
});
