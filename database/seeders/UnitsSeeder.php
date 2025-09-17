<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        // Quadrants
        $quadrants = ['I', 'II', 'III', 'IV'];

        // Teeth
        $teeth = [
            // Permanent
            '11',
            '12',
            '13',
            '14',
            '15',
            '16',
            '17',
            '18',
            '21',
            '22',
            '23',
            '24',
            '25',
            '26',
            '27',
            '28',
            '31',
            '32',
            '33',
            '34',
            '35',
            '36',
            '37',
            '38',
            '41',
            '42',
            '43',
            '44',
            '45',
            '46',
            '47',
            '48',
            // Primary
            '51',
            '52',
            '53',
            '54',
            '55',
            '61',
            '62',
            '63',
            '64',
            '65',
            '71',
            '72',
            '73',
            '74',
            '75',
            '81',
            '82',
            '83',
            '84',
            '85',
            // Special
            'SUPERNUMERARY',
            'ROOT FRAGMENT',
        ];

        // Arch
        $arches = ['UPPER', 'LOWER'];

        // Surfaces
        $surfaces = [
            'BUCCAL',
            'CERVICAL',
            'DISTAL',
            'FACIAL',
            'INCISAL',
            'LINGUAL',
            'MESIAL',
            'OCCLUSAL',
            'PALATAL',
        ];

        // Insert into separate tables
        foreach ($quadrants as $quadrant) {
            DB::table('units_quadrants')->insert(['name' => $quadrant]);
        }

        foreach ($teeth as $tooth) {
            DB::table('units_tooth')->insert(['name' => $tooth]);
        }

        foreach ($arches as $arch) {
            DB::table('units_arch')->insert(['name' => $arch]);
        }

        foreach ($surfaces as $surface) {
            DB::table('units_surfaces')->insert(['name' => $surface]);
        }
    }
}
