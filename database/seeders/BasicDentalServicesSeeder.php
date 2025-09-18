<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BasicDentalServicesSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Consultation'],
            ['name' => 'Treatment of sores, blisters'],
            ['name' => 'Temporary fillings'],
            ['name' => 'Simple tooth extraction'],
            ['name' => 'Recementation of fixed bridges, crowns, jackets, inlays/onlays'],
            ['name' => 'Adjustment of Dentures'],
        ];

        DB::table('basic_dental_services')->insert($services);
    }
}
