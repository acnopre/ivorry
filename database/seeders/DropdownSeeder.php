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
            ['name' => 'PROPRIETORSHIP'],
            ['name' => 'PARTNERSHIP'],
            ['name' => 'CORPORATION'],
            ['name' => 'GENERAL PROFESSIONAL PARTNERSHIP'],
            ['name' => 'ONE PERSON CORPORATION'],

        ]);



        DB::table('account_types')->insert([
            ['name' => 'SAVINGS'],
            ['name' => 'CURRENT'],
        ]);

        DB::table('vat_types')->insert([
            ['name' => 'VAT 12%'],
            ['name' => 'NON-VAT'],
            ['name' => 'VAT EXEMPT'],
            ['name' => 'VAT ZERO'],
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

        DB::table('update_info_1903_types')->insert([
            ['name' => 'CHANGE IN BUSINESS NAME'],
            ['name' => 'CHANGE IN ADDRESS'],
            ['name' => 'CHANGE IN TAX TYPE'],
        ]);
    }
}
