<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DentalPlanBenefitsSeeder extends Seeder
{
    public function run(): void
    {
        $benefits = [
            // BASIC DENTAL SERVICES
            ['category' => 'BASIC DENTAL SERVICES', 'service_name' => 'Consultation', 'unit' => 'SESSION', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'BASIC DENTAL SERVICES', 'service_name' => 'Treatment of sores, blisters', 'unit' => 'QUADRANT', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'BASIC DENTAL SERVICES', 'service_name' => 'Temporary fillings', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'BASIC DENTAL SERVICES', 'service_name' => 'Simple tooth extraction', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'BASIC DENTAL SERVICES', 'service_name' => 'Recementation of fixed bridges, crowns, jackets, inlays/onlays', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'BASIC DENTAL SERVICES', 'service_name' => 'Adjustment of Dentures', 'unit' => 'ARCH', 'limits' => 'TO BE ENTERED BASED ON MOA'],

            // PLAN ENHANCEMENTS
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Oral Prophylaxis', 'unit' => 'SESSION', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Permanent Filling (per tooth)', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Permanent Filling (per Surface)', 'unit' => 'SURFACE', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Desensitization of Hypersensitive teeth', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Fluoride Brushing', 'unit' => 'ARCH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Incision and Drainage', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Peri-apical Xray', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Panoramic Xray', 'unit' => 'SESSION', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Complicated/Difficult Extraction', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Odontectomy (Removal of Impacted tooth)', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Root Canal Treatment (per canal)', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Root Canal Treatment (per tooth)', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Jacket Crowns', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Dentures', 'unit' => 'ARCH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Pit and Fissure Sealants', 'unit' => 'TOOTH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Topical Fluoride Application', 'unit' => 'ARCH', 'limits' => 'TO BE ENTERED BASED ON MOA'],
            ['category' => 'PLAN ENHANCEMENTS', 'service_name' => 'Minor Soft tissue Surgery', 'unit' => 'QUADRANT', 'limits' => 'TO BE ENTERED BASED ON MOA'],
        ];

        DB::table('dental_plan_benefits')->insert($benefits);
    }
}
