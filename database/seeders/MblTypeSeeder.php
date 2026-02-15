<?php

namespace Database\Seeders;

use App\Models\MblType;
use Illuminate\Database\Seeder;

class MblTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MblType::insert([
            ['name' => 'Procedural', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Fixed', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
