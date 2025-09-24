<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DropdownSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tax_types')->insert([
            ['name' => 'VAT'],
            ['name' => 'NON-VAT'],
            ['name' => '0%'],
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
    }
}

