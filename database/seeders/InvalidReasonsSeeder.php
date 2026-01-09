<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvalidReasonsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('invalid_reasons')->insert([
            [
                'name' => 'Procedure not availed, not confirmed by member',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Late filing, accreditation issues, system mismatches',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Exceeding benefit caps, exclusions applied',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
