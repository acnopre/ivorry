<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccreditationStatus;

class AccreditationStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'ACTIVE',
            'INACTIVE',
            'SILENT',
            'SPECIFIC ACCOUNT',
        ];

        foreach ($statuses as $status) {
            AccreditationStatus::firstOrCreate(['name' => $status]);
        }
    }
}
