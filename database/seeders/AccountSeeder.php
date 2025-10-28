<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('accounts')->insert([
            [
                'company_name'     => 'MediCare Health Inc.',
                'policy_code'      => 'POL-' . strtoupper(Str::random(6)),
                'hip'              => 'HIP-001',
                'card_used'        => 'CARD-1001',
                'effective_date'   => now()->subMonths(2)->toDateString(),
                'expiration_date'  => now()->addYear()->toDateString(),
                'endorsement_type' => 'NEW',
                'account_status'   => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'company_name'     => 'CarePlus Insurance Corp.',
                'policy_code'      => 'POL-' . strtoupper(Str::random(6)),
                'hip'              => 'HIP-002',
                'card_used'        => 'CARD-1002',
                'effective_date'   => now()->subMonths(5)->toDateString(),
                'expiration_date'  => now()->addMonths(7)->toDateString(),
                'endorsement_type' => 'RENEWAL',
                'account_Status'   => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'company_name'     => 'WellLife HMO',
                'policy_code'      => 'POL-' . strtoupper(Str::random(6)),
                'hip'              => 'HIP-003',
                'card_used'        => 'CARD-1003',
                'effective_date'   => now()->subYear()->toDateString(),
                'expiration_date'  => now()->addMonths(3)->toDateString(),
                'endorsement_type' => 'AMENDMENT',
                'account_status'   => 0,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'company_name'     => 'Global Health Partners',
                'policy_code'      => 'POL-' . strtoupper(Str::random(6)),
                'hip'              => null,
                'card_used'        => 'CARD-1004',
                'effective_date'   => now()->toDateString(),
                'expiration_date'  => now()->addYear()->toDateString(),
                'endorsement_type' => 'NEW',
                'account_status'   => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'company_name'     => 'PrimeCare Solutions',
                'policy_code'      => 'POL-' . strtoupper(Str::random(6)),
                'hip'              => 'HIP-005',
                'card_used'        => null,
                'effective_date'   => now()->subMonths(1)->toDateString(),
                'expiration_date'  => now()->addMonths(11)->toDateString(),
                'endorsement_type' => 'RENEWAL',
                'account_status'   => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);
    }
}
