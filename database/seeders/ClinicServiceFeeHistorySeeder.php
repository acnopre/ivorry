<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClinicServiceFeeHistorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = DB::table('clinic_services')
            ->whereNotNull('fee')
            ->get();

        foreach ($rows as $row) {
            $exists = DB::table('clinic_service_fee_histories')
                ->where('clinic_id', $row->clinic_id)
                ->where('service_id', $row->service_id)
                ->exists();

            if ($exists) continue;

            DB::table('clinic_service_fee_histories')->insert([
                'clinic_id'      => $row->clinic_id,
                'service_id'     => $row->service_id,
                'old_fee'        => null,
                'new_fee'        => $row->fee,
                'effective_date' => $row->created_at ?? now(),
                'approved_by'    => null,
                'created_by'     => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }
}
