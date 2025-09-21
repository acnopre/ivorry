<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Specializations;

class SpecializationSeeder extends Seeder
{
    
    public function run(): void
    {
        $specializations = [
            'AESTHETIC/ COSMETIC DENTISTRY',
            'BIOMIMETIC/ BIOLOGICAL DENTISTRY',
            'DENTAL IMPLANTOLOGY',
            'ENDODONTICS',
            'GENERAL DENTISTRY',
            'ORAL AND MAXILLOFACIAL SURGERY',
            'ORAL SURGERY',
            'ORTHODONTICS',
            'PEDIATRIC DENTISTRY',
            'PERIODONTICS',
            'RADIOGRAPHY (PANORAMIC)',
            'RADIOGRAPHY (PERIAPICAL)',
            'TMJ DISORDER',
        ];

        foreach ($specializations as $name) {
            Specializations::firstOrCreate(['name' => $name]);
        }
    }
}
