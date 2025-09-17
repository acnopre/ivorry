<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BenefitsSeeder extends Seeder
{
    public function run(): void
    {
        $benefits = [
            // BASIC DENTAL SERVICES
            ['category' => 'BASIC DENTAL SERVICES', 'name' => 'Consultation'],
            ['category' => 'BASIC DENTAL SERVICES', 'name' => 'Treatment of sores, blisters'],
            ['category' => 'BASIC DENTAL SERVICES', 'name' => 'Temporary fillings'],
            ['category' => 'BASIC DENTAL SERVICES', 'name' => 'Simple tooth extraction'],
            ['category' => 'BASIC DENTAL SERVICES', 'name' => 'Recementation of fixed bridges, crowns, jackets, inlays/onlays'],
            ['category' => 'BASIC DENTAL SERVICES', 'name' => 'Adjustment of Dentures'],

            // PLAN ENHANCEMENTS
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Oral Prophylaxis'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Permanent Filling (per tooth)'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Permanent Filling (per Surface)'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Desensitization of Hypersensitive teeth'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Fluoride Brushing'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Incision and Drainage'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Peri-apical Xray'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Panoramic Xray'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Complicated/Difficult Extraction'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Odontectomy (Removal of Impacted tooth)'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Root Canal Treatment (per canal)'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Root Canal Treatment (per tooth)'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Jacket Crowns'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Dentures'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Pit and Fissure Sealants'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Topical Fluoride Application'],
            ['category' => 'PLAN ENHANCEMENTS', 'name' => 'Minor Soft tissue Surgery'],
        ];



        DB::table('benefits')->insert($benefits);
    }
}
