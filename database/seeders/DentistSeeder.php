<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DentistSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('dentists')->insert([
            [
                'clinic_id' => 1,
                'name' => 'Dr. Juan Dela Cruz',
                'specialty' => 'Orthodontics',
                'status' => 'active',
            ],
            [
                'clinic_id' => 1,
                'name' => 'Dr. Maria Santos',
                'specialty' => 'Pediatric Dentistry',
                'status' => 'active',
            ],
            [
                'clinic_id' => 1,
                'name' => 'Dr. Jose Rizal',
                'specialty' => 'Endodontics',
                'status' => 'inactive',
            ],
            [
                'clinic_id' => 1,
                'name' => 'Dr. Ana Lopez',
                'specialty' => 'Prosthodontics',
                'status' => 'active',
            ],
            [
                'clinic_id' => 1,
                'name' => 'Dr. Pedro Cruz',
                'specialty' => 'Oral Surgery',
                'status' => 'active',
            ],
        ]);
    }
}
