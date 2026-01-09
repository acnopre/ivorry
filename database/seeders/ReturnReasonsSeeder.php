<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReturnReasonsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('return_reasons')->insert([
            [
                'name' => 'Missing forms, incomplete attachments, wrong patient info',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Wrong dental codes, mismatched diagnosis/procedure',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Missing physician signature',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Missing clinic branch',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Charges not itemized correctly',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
