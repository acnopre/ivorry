<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HipSeeder extends Seeder
{
    public function run(): void
    {
        $hips = [
            'ETIQA LIFE AND GENERAL ASSURANCE PHILIPPINES, INC.',
            'MARSH PHILIPPINES, INC.',
            'Magsaysay Houlder Insurance Brokers Inc.',
            'OMNI International Consultants, Inc.',
            'Generali Life Assurance Phils, Inc.',
            'KWIK CARE',
            'MM ROYAL CARE',
        ];

        $data = [];
        foreach ($hips as $hip) {
            $data[] = [
                'name' => $hip,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('hips')->insert($data);
    }
}
