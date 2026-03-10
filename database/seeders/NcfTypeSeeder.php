<?php

namespace Database\Seeders;

use App\Models\NcfType;
use Illuminate\Database\Seeder;

class NcfTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Crédito Fiscal',
                'prefix' => 'B01',
            ],
            [
                'name' => 'Gubernamental',
                'prefix' => 'B15',
            ],
        ];

        foreach ($types as $type) {
            NcfType::updateOrCreate(
                ['prefix' => $type['prefix']],
                [
                    'name' => $type['name'],
                    'active' => true,
                ]
            );
        }
    }
}
