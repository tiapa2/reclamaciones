<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'ARS SENASA-PENSIONADO', 'rnc' => '401-51645-4', 'email' => strtolower('respuestasalprestador@senasa.gob.do'), 'phone' => '809-732-3821'],
            ['name' => 'ARS APS', 'rnc' => '101-78868-2', 'email' => strtolower('contacto@arsaps.com'), 'phone' => '809-540-7270'],
            ['name' => 'ARS YUNEN', 'rnc' => '101-87588-7', 'email' => strtolower('info@arsyunen.com'), 'phone' => '809-540-0901'],
            ['name' => 'ARS FUTURO', 'rnc' => '101-55754-2', 'email' => strtolower('atencion@arsfuturo.com'), 'phone' => '809-686-1218'],
            ['name' => 'ARS RENACER', 'rnc' => '101-88648-1', 'email' => strtolower('servicio@arsrenacer.com'), 'phone' => '809-621-9999'],
            ['name' => 'ARS UNIVERSAL', 'rnc' => '124-00560-4', 'email' => strtolower('soporte@arsuniversal.com'), 'phone' => '809-544-7750'],
            ['name' => 'ARS RESERVAS', 'rnc' => '430-08988-5', 'email' => strtolower('info@arsreservas.com'), 'phone' => '809-334-5505'],
            ['name' => 'ARS MONUMENTAL', 'rnc' => '130-05800-8', 'email' => strtolower('contacto@arsmonumental.com'), 'phone' => '809-825-0000'],
            ['name' => 'ARS CMD', 'rnc' => '401-50101-5', 'email' => strtolower('soporte@arscmd.com'), 'phone' => '809-534-5488'],
            ['name' => 'ARS UASD', 'rnc' => '430-05927-7', 'email' => strtolower('SERVICIO@ARSUASD.COM'), 'phone' => '809-2213040'],
            ['name' => 'ARS SEMMA', 'rnc' => '401-05266-2', 'email' => strtolower('SERVICIO@ARSEMMA.GOB.DO'), 'phone' => '809-688-6646'],
            ['name' => 'ARS SIMAG', 'rnc' => '101-06412-9', 'email' => strtolower('atencion@simag.com'), 'phone' => '809-555-0808'],
            ['name' => 'ARS HUMANO', 'rnc' => '102-01717-4', 'email' => strtolower('contacto@arshumano.com'), 'phone' => '809-476-3535'],
            ['name' => 'ARS META SALUD', 'rnc' => '124-03242-3', 'email' => strtolower('atencion@arsmeta.com'), 'phone' => '809-285-3005'],
            // Duplicate RNC (430-08988-5). Upsert mantendrá 1 solo, y lo actualizará con el último valor:
            ['name' => 'ARS RESERVAS', 'rnc' => '430-08988-5', 'email' => strtolower('atencion@arsbanreservas.com'), 'phone' => '809-334-5505'],

            ['name' => 'ARS BANCO CENTRAL', 'rnc' => '430-02771-5', 'email' => strtolower('BANCOCENTRAL@GMAIL.COM'), 'phone' => '809-221-9111'],
            // Duplicate RNC (401-51645-4). Upsert mantendrá 1 solo, último valor:
            ['name' => 'ARS SENASA-CONTRIBUTIVO', 'rnc' => '401-51645-4', 'email' => strtolower('respuestasalprestador@senasa.gob.do'), 'phone' => '809-732-3821'],

            ['name' => 'ARS PRIMERA', 'rnc' => '101-86442-7', 'email' => strtolower('SOLICITUDESPSS@HUMANO.COM'), 'phone' => '809-476-3333'],
            ['name' => 'ARS DIDA', 'rnc' => '00256789012', 'email' => strtolower('contacto@arsdida.com'), 'phone' => '809-555-1515'],
            ['name' => 'ARS Maestro', 'rnc' => '00267890123', 'email' => strtolower('soporte@arsmaestro.com'), 'phone' => '809-555-1616'],
            ['name' => 'ARS ProSalud', 'rnc' => '00278901234', 'email' => strtolower('info@arsprosalud.com'), 'phone' => '809-555-1717'],
            ['name' => 'ARS Salud Segura', 'rnc' => '00289012345', 'email' => strtolower('atencion@arssaludsegura.com'), 'phone' => '809-555-1818'],
            ['name' => 'ARS Vida y Salud', 'rnc' => '00290123456', 'email' => strtolower('contacto@arsvida.com'), 'phone' => '809-555-1919'],
            ['name' => 'ARS BHD', 'rnc' => '00312345678', 'email' => strtolower('soporte@arsbhd.com'), 'phone' => '809-555-2121'],
            ['name' => 'ARS Popular', 'rnc' => '00323456789', 'email' => strtolower('contacto@arspopular.com'), 'phone' => '809-555-2222'],
            ['name' => 'ARS Scotia', 'rnc' => '00334567890', 'email' => strtolower('info@arsscotia.com'), 'phone' => '809-555-2323'],
            ['name' => 'ARS Vimenca', 'rnc' => '00356789012', 'email' => strtolower('soporte@arsvimenca.com'), 'phone' => '809-555-2525'],
            ['name' => 'ARS Progreso', 'rnc' => '00390123456', 'email' => strtolower('info@arsprogreso.com'), 'phone' => '809-555-2929'],
            ['name' => 'ARS Integral', 'rnc' => '00401234567', 'email' => strtolower('contacto@arsintegral.com'), 'phone' => '809-555-3030'],
            ['name' => 'ARS MAPFRE SALUD', 'rnc' => '101-76158-1', 'email' => strtolower('SERVICIOS@MAPFRESALUDARS.COM'), 'phone' => '809-472-1515'],
        ];

        // Recomendado: evita duplicados y actualiza por RNC
        DB::table('insurers')->upsert(
            $rows,
            ['rnc'],                 // clave unica (ideal tener unique index en DB)
            ['name', 'email', 'phone'] // campos a actualizar si ya existe
        );
    }
}
