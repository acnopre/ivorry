<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $basicServices = [
            ['name' => 'Consultation', 'type' => 'basic', 'unit_type' => 'Session'],
            ['name' => 'Treatment of sores, blisters', 'type' => 'basic', 'unit_type' => 'Quadrant'],
            ['name' => 'Temporary fillings', 'type' => 'basic', 'unit_type' => 'Tooth'],
            ['name' => 'Simple tooth extraction', 'type' => 'basic', 'unit_type' => 'Tooth'],
            ['name' => 'Recementation of fixed bridges, crowns, jackets, inlays/ onlays', 'type' => 'basic', 'unit_type' => 'Tooth'],
            ['name' => 'Adjustment of Dentures', 'type' => 'basic', 'unit_type' => 'Arch'],
        ];

        $enhancementServices = [
            ['name' => 'Oral Prophylaxis', 'type' => 'enhancement', 'unit_type' => 'Session'],
            ['name' => 'Permanent Filling (per tooth)', 'type' => 'enhancement', 'unit_type' => 'Tooth'],
            ['name' => 'Permanent filling (per Surface)', 'type' => 'enhancement', 'unit_type' => 'Surface'],
            ['name' => 'Desensitization of Hypersensitive teeth', 'type' => 'enhancement', 'unit_type' => 'Tooth'],
            ['name' => 'Fluoride Brushing', 'type' => 'enhancement', 'unit_type' => 'Arch'],
            ['name' => 'Incision and Drainage', 'type' => 'enhancement', 'unit_type' => 'Tooth'],
            ['name' => 'Peri-apical Xray', 'type' => 'enhancement', 'unit_type' => 'Tooth'],
            ['name' => 'Panoramic Xray', 'type' => 'enhancement', 'unit_type' => 'Session'],
            ['name' => 'Complicated/ Difficult Extraction', 'type' => 'enhancement', 'unit_type' => 'Tooth'],
            ['name' => 'Odontectomy (Removal of Impacted tooth)', 'type' => 'enhancement', 'unit_type' => 'Tooth'],
            ['name' => 'Root Canal Treatment (per canal)', 'type' => 'enhancement', 'unit_type' => 'Canal'],
            ['name' => 'Root Canal Treatment (per tooth)', 'type' => 'enhancement', 'unit_type' => 'Tooth'],
            ['name' => 'Jacket Crowns', 'type' => 'enhancement', 'unit_type' => 'Tooth'],
            ['name' => 'Dentures', 'type' => 'enhancement', 'unit_type' => 'Arch'],
            ['name' => 'Pit and Fissure Sealants', 'type' => 'enhancement', 'unit_type' => 'Tooth'],
            ['name' => 'Topical Fluoride Application', 'type' => 'enhancement', 'unit_type' => 'Arch'],
            ['name' => 'Minor Soft tissue Surgery', 'type' => 'enhancement', 'unit_type' => 'Quadrant'],
        ];

        DB::table('services')->insert(array_merge($basicServices, $enhancementServices));
    }
}
