<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $basicServices = [
            ['name' => 'Consultation', 'slug' => 'consultation', 'type' => 'basic', 'unit_type' => 'Session', 'max_per_date' => 1],
            ['name' => 'Treatment of sores, blisters', 'slug' => 'treatment_of_sores_blisters', 'type' => 'basic', 'unit_type' => 'Quadrant', 'max_per_date' => 4],
            ['name' => 'Temporary fillings', 'slug' => 'temporary_fillings', 'type' => 'basic', 'unit_type' => 'Tooth', 'max_per_date' => 3],
            ['name' => 'Simple tooth extraction', 'slug' => 'simple_tooth_extraction', 'type' => 'basic', 'unit_type' => 'Tooth', 'max_per_date' => 3],
            ['name' => 'Recementation of fixed bridges, crowns, jackets, inlays/ onlays', 'slug' => 'recementation_of_fixed_bridges_crowns_jackets_inlays_onlays', 'type' => 'basic', 'unit_type' => 'Tooth', 'max_per_date' => 5],
            ['name' => 'Adjustment of Dentures', 'slug' => 'adjustment_of_dentures', 'type' => 'basic', 'unit_type' => 'Arch', 'max_per_date' => 2],
        ];

        $enhancementServices = [
            ['name' => 'Oral Prophylaxis', 'slug' => 'oral_prophylaxis', 'type' => 'enhancement', 'unit_type' => 'Session', 'max_per_date' => 1],
            ['name' => 'Permanent Filling (per tooth)', 'slug' => 'permanent_filling_per_tooth', 'type' => 'enhancement', 'unit_type' => 'Tooth', 'max_per_date' => 3],
            ['name' => 'Permanent filling (per Surface)', 'slug' => 'permanent_filling_per_surface', 'type' => 'enhancement', 'unit_type' => 'Surface', 'max_per_date' => 5],
            ['name' => 'Desensitization of Hypersensitive teeth', 'slug' => 'desensitization_of_hypersensitive_teeth', 'type' => 'enhancement', 'unit_type' => 'Tooth', 'max_per_date' => 2],
        ];

        $specialServices = [
            ['name' => 'Fluoride Brushing', 'slug' => 'fluoride_brushing', 'type' => 'special', 'unit_type' => 'Arch', 'max_per_date' => 2],
            ['name' => 'Incision and Drainage', 'slug' => 'incision_and_drainage', 'type' => 'special', 'unit_type' => 'Quadrant', 'max_per_date' => 2],
            ['name' => 'Peri-apical Xray', 'slug' => 'peri_apical_xray', 'type' => 'special', 'unit_type' => 'Tooth', 'max_per_date' => 2],
            ['name' => 'Panoramic Xray', 'slug' => 'panoramic_xray', 'type' => 'special', 'unit_type' => 'Session', 'max_per_date' => 1],
            ['name' => 'Complicated/ Difficult Extraction', 'slug' => 'complicated_difficult_extraction', 'type' => 'special', 'unit_type' => 'Tooth', 'max_per_date' => 1],
            ['name' => 'Odontectomy (Removal of Impacted tooth)', 'slug' => 'odontectomy_removal_of_impacted_tooth', 'type' => 'special', 'unit_type' => 'Tooth', 'max_per_date' => 1],
            ['name' => 'Root Canal Treatment (per canal)', 'slug' => 'root_canal_treatment_per_canal', 'type' => 'special', 'unit_type' => 'Canal', 'max_per_date' => 4],
            ['name' => 'Root Canal Treatment (per tooth)', 'slug' => 'root_canal_treatment_per_tooth', 'type' => 'special', 'unit_type' => 'Tooth', 'max_per_date' => 1],
            ['name' => 'Jacket Crowns', 'slug' => 'jacket_crowns', 'type' => 'special', 'unit_type' => 'Tooth', 'max_per_date' => 1],
            ['name' => 'Dentures', 'slug' => 'dentures', 'type' => 'special', 'unit_type' => 'Arch', 'max_per_date' => 1],
            ['name' => 'Pit and Fissure Sealants', 'slug' => 'pit_and_fissure_sealants', 'type' => 'special', 'unit_type' => 'Tooth', 'max_per_date' => 3],
            ['name' => 'Topical Fluoride Application', 'slug' => 'topical_fluoride_application', 'type' => 'special', 'unit_type' => 'Arch', 'max_per_date' => 2],
            ['name' => 'Minor Soft tissue Surgery', 'slug' => 'minor_soft_tissue_surgery', 'type' => 'special', 'unit_type' => 'Quadrant', 'max_per_date' => 1],
        ];


        DB::table('services')->insert(array_merge($basicServices, $enhancementServices, $specialServices));
    }
}
