<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $basicServices = [
            ['name' => 'Consultation', 'type' => 'basic'],
            ['name' => 'Treatment of sores, blisters', 'type' => 'basic'],
            ['name' => 'Temporary fillings', 'type' => 'basic'],
            ['name' => 'Simple tooth extraction', 'type' => 'basic'],
            ['name' => 'Recementation of fixed bridges, crowns, jackets, inlays/onlays', 'type' => 'basic'],
            ['name' => 'Adjustment of Dentures', 'type' => 'basic'],
        ];

        $enhancementServices = [
            ['name' => 'Oral Prophylaxis', 'type' => 'enhancement'],
            ['name' => 'Permanent Filling (per tooth)', 'type' => 'enhancement'],
            ['name' => 'Permanent Filling (per Surface)', 'type' => 'enhancement'],
            ['name' => 'Desensitization of Hypersensitive teeth', 'type' => 'enhancement'],
            ['name' => 'Fluoride Brushing', 'type' => 'enhancement'],
            ['name' => 'Incision and Drainage', 'type' => 'enhancement'],
            ['name' => 'Peri-apical Xray', 'type' => 'enhancement'],
            ['name' => 'Panoramic Xray', 'type' => 'enhancement'],
            ['name' => 'Complicated/ Difficult Extraction', 'type' => 'enhancement'],
            ['name' => 'Odontectomy (Removal of Impacted tooth)', 'type' => 'enhancement'],
            ['name' => 'Root Canal Treatment (per canal)', 'type' => 'enhancement'],
            ['name' => 'Root Canal Treatment (per tooth)', 'type' => 'enhancement'],
            ['name' => 'Jacket Crowns', 'type' => 'enhancement'],
            ['name' => 'Dentures', 'type' => 'enhancement'],
            ['name' => 'Pit and Fissure Sealants', 'type' => 'enhancement'],
            ['name' => 'Topical Fluoride Application', 'type' => 'enhancement'],
            ['name' => 'Minor Soft tissue Surgery', 'type' => 'enhancement'],
        ];

        DB::table('services')->insert(array_merge($basicServices, $enhancementServices));
    }
}
