<?php

namespace Database\Seeders;

use App\Models\SystemVersion;
use Illuminate\Database\Seeder;

class SystemVersionSeeder extends Seeder
{
    public function run(): void
    {
        SystemVersion::create([
            'version' => '1.0.0',
            'notes' => 'Initial release',
            'released_at' => now(),
        ]);
    }
}
