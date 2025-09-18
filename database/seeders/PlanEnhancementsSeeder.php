<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanEnhancementsSeeder extends Seeder
{
    public function run(): void
    {
        $enhancements = [
            ['name' => 'Oral Prophylaxis'],
            ['name' => 'Permanent Filling (per tooth)'],
            ['name' => 'Permanent Filling (per Surface)'],
            ['name' => 'Desensitization of Hypersensitive teeth'],
            ['name' => 'Fluoride Brushing'],
            ['name' => 'Incision and Drainage'],
            ['name' => 'Peri-apical Xray'],
            ['name' => 'Panoramic Xray'],
            ['name' => 'Complicated/ Difficult Extraction'],
            ['name' => 'Odontectomy (Removal of Impacted tooth)'],
            ['name' => 'Root Canal Treatment (per canal)'],
            ['name' => 'Root Canal Treatment (per tooth)'],
            ['name' => 'Jacket Crowns'],
            ['name' => 'Dentures'],
            ['name' => 'Pit and Fissure Sealants'],
            ['name' => 'Topical Fluoride Application'],
            ['name' => 'Minor Soft tissue Surgery'],
        ];

        DB::table('plan_enhancements')->insert($enhancements);
    }
}
