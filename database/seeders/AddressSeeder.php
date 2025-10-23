<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        ini_set('memory_limit', '1024M'); // optional safeguard

        $json = File::get(database_path('data/address2019.json'));
        $data = json_decode($json, true);

        $regionId = 1;
        $provinceId = 1;
        $municipalityId = 1;

        foreach ($data as $regionCode => $regionData) {
            $regionId = DB::table('regions')->insertGetId([
                'code' => $regionCode,
                'name' => $regionData['region_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($regionData['province_list'] as $provinceName => $provinceData) {
                $provinceId = DB::table('provinces')->insertGetId([
                    'region_id' => $regionId,
                    'name' => $provinceName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($provinceData['municipality_list'] as $municipalityName => $municipalityData) {
                    $municipalityId = DB::table('municipalities')->insertGetId([
                        'province_id' => $provinceId,
                        'name' => $municipalityName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Barangays: insert in chunks of 500 for safety
                    $barangayChunk = [];
                    foreach ($municipalityData['barangay_list'] as $barangayName) {
                        $barangayChunk[] = [
                            'municipality_id' => $municipalityId,
                            'name' => $barangayName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        if (count($barangayChunk) >= 500) {
                            DB::table('barangays')->insert($barangayChunk);
                            $barangayChunk = [];
                        }
                    }

                    if (count($barangayChunk) > 0) {
                        DB::table('barangays')->insert($barangayChunk);
                    }
                }
            }
        }
    }
}
