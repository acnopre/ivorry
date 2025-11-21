<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DropdownSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tax_types')->insert([
            ['name' => '2%'],
            ['name' => '5%'],
            ['name' => '10%'],
            ['name' => '15%'],
        ]);

        DB::table('business_types')->insert([
            ['name' => 'SOLE PROPRIETOR'],
            ['name' => 'PARTNERSHIP'],
            ['name' => 'CORPORATION'],
        ]);

        DB::table('account_types')->insert([
            ['name' => 'SAVINGS'],
            ['name' => 'CURRENT'],
        ]);

        DB::table('vat_type')->insert([
            ['name' => 'VAT'],
            ['name' => 'NON-VAT'],
            ['name' => 'ZERO VAT'],
        ]);
        DB::table('surfaces')->insert([
            ['name' => 'BUCCAL'],
            ['name' => 'CERVICAL'],
            ['name' => 'DISTAL'],
            ['name' => 'FACIAL'],
            ['name' => 'INCISAL'],
            ['name' => 'LINGUAL'],
            ['name' => 'MESIAL'],
            ['name' => 'OCCLUSAL'],
            ['name' => 'PALATAL'],
        ]);
    }
}
