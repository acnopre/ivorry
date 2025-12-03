<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EndorsementType;

class EndorsementTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'NEW',
            'RENEWAL',
            'RENEWED',
            'AMENDMENT',
            'AMENDED'
        ];

        foreach ($types as $type) {
            EndorsementType::firstOrCreate(['name' => $type]);
        }
    }
}
