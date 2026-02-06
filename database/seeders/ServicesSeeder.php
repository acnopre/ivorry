<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $basicServices = [
            ['name' => 'Consultation', 'type' => 'basic', 'unit_type' => 'Session', 'max_per_date' => 1],
            ['name' => 'Treatment of sores, blisters', 'type' => 'basic', 'unit_type' => 'Quadrant', 'max_per_date' => 4],
            ['name' => 'Temporary fillings', 'type' => 'basic', 'unit_type' => 'Tooth', 'max_per_date' => 3],
            ['name' => 'Simple tooth extraction', 'type' => 'basic', 'unit_type' => 'Tooth', 'max_per_date' => 3],
            ['name' => 'Recementation of fixed bridges, crowns, jackets, inlays/ onlays', 'type' => 'basic', 'unit_type' => 'Tooth', 'max_per_date' => 5],
            ['name' => 'Adjustment of Dentures', 'type' => 'basic', 'unit_type' => 'Arch', 'max_per_date' => 2],
        ];

        $enhancementServices = [
            ['name' => 'Oral Prophylaxis', 'type' => 'enhancement', 'unit_type' => 'Session', 'max_per_date' => 1],
            ['name' => 'Permanent Filling (per tooth)', 'type' => 'enhancement', 'unit_type' => 'Tooth', 'max_per_date' => 3],
            ['name' => 'Permanent filling (per Surface)', 'type' => 'enhancement', 'unit_type' => 'Surface', 'max_per_date' => 5],
            ['name' => 'Desensitization of Hypersensitive teeth', 'type' => 'enhancement', 'unit_type' => 'Tooth', 'max_per_date' => 2],
        ];

        $specialServices = [
            ['name' => 'Fluoride Brushing', 'type' => 'special', 'unit_type' => 'Arch', 'max_per_date' => 2],
            ['name' => 'Incision and Drainage', 'type' => 'special', 'unit_type' => 'Quadrant', 'max_per_date' => 2],
            ['name' => 'Peri-apical Xray', 'type' => 'special', 'unit_type' => 'Tooth', 'max_per_date' => 2],
            ['name' => 'Panoramic Xray', 'type' => 'special', 'unit_type' => 'Session', 'max_per_date' => 1],
            ['name' => 'Complicated/ Difficult Extraction', 'type' => 'special', 'unit_type' => 'Tooth', 'max_per_date' => 1],
            ['name' => 'Odontectomy (Removal of Impacted tooth)', 'type' => 'special', 'unit_type' => 'Tooth', 'max_per_date' => 1],
            ['name' => 'Root Canal Treatment (per canal)', 'type' => 'special', 'unit_type' => 'Canal', 'max_per_date' => 4],
            ['name' => 'Root Canal Treatment (per tooth)', 'type' => 'special', 'unit_type' => 'Tooth', 'max_per_date' => 1],
            ['name' => 'Jacket Crowns', 'type' => 'special', 'unit_type' => 'Tooth', 'max_per_date' => 1],
            ['name' => 'Dentures', 'type' => 'special', 'unit_type' => 'Arch', 'max_per_date' => 1],
            ['name' => 'Pit and Fissure Sealants', 'type' => 'special', 'unit_type' => 'Tooth', 'max_per_date' => 3],
            ['name' => 'Topical Fluoride Application', 'type' => 'special', 'unit_type' => 'Arch', 'max_per_date' => 2],
            ['name' => 'Minor Soft tissue Surgery', 'type' => 'special', 'unit_type' => 'Quadrant', 'max_per_date' => 1],
        ];

        DB::table('services')->insert(array_merge($basicServices, $enhancementServices, $specialServices));
    }
}
