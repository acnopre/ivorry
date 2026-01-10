<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HipSeeder extends Seeder
{
    public function run(): void
    {
        $hips = [];

        for ($i = 1; $i <= 10; $i++) {
            $hips[] = [
                'name' => 'HIP-' . strtoupper(Str::random(4)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('hips')->insert($hips);
    }
}
