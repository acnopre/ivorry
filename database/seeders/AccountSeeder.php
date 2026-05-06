<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Hip;
use App\Models\Service;
use App\Models\AccountService;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $hip = Hip::first();

        if (! $hip) {
            $this->command->warn('No HIP found — skipping AccountSeeder.');
            return;
        }

        $accounts = [
            [
                'company_name'         => 'MediCare Health Inc.',
                'policy_code'          => 'POL-MEDICARE',
                'plan_type'            => 'INDIVIDUAL',
                'coverage_type'        => 'DEFAULT',
                'coverage_period_type' => 'ACCOUNT',
                'mbl_type'             => 'Procedural',
                'mbl_amount'           => null,
                'endorsement_type'     => 'NEW',
                'endorsement_status'   => 'APPROVED',
                'account_status'       => 'active',
                'effective_date'       => now()->subMonths(2)->toDateString(),
                'expiration_date'      => now()->addMonths(10)->toDateString(),
            ],
            [
                'company_name'         => 'CarePlus Insurance Corp.',
                'policy_code'          => 'POL-CAREPLUS',
                'plan_type'            => 'INDIVIDUAL',
                'coverage_type'        => 'ALL_PRINCIPAL',
                'coverage_period_type' => 'ACCOUNT',
                'mbl_type'             => 'Fixed',
                'mbl_amount'           => 50000,
                'endorsement_type'     => 'NEW',
                'endorsement_status'   => 'APPROVED',
                'account_status'       => 'active',
                'effective_date'       => now()->subMonths(1)->toDateString(),
                'expiration_date'      => now()->addMonths(11)->toDateString(),
            ],
            [
                'company_name'         => 'WellLife HMO',
                'policy_code'          => 'POL-WELLLIFE',
                'plan_type'            => 'INDIVIDUAL',
                'coverage_type'        => 'ALL_DEPENDENT',
                'coverage_period_type' => 'MEMBER',
                'mbl_type'             => 'Procedural',
                'mbl_amount'           => null,
                'endorsement_type'     => 'NEW',
                'endorsement_status'   => 'APPROVED',
                'account_status'       => 'active',
                'effective_date'       => now()->subMonths(3)->toDateString(),
                'expiration_date'      => now()->addMonths(9)->toDateString(),
            ],
            [
                'company_name'         => 'Global Health Partners',
                'policy_code'          => 'POL-GLOBAL',
                'plan_type'            => 'SHARED',
                'coverage_type'        => 'DEFAULT',
                'coverage_period_type' => 'ACCOUNT',
                'mbl_type'             => 'Fixed',
                'mbl_amount'           => 80000,
                'endorsement_type'     => 'NEW',
                'endorsement_status'   => 'APPROVED',
                'account_status'       => 'active',
                'effective_date'       => now()->subMonths(1)->toDateString(),
                'expiration_date'      => now()->addMonths(11)->toDateString(),
            ],
            [
                'company_name'         => 'PrimeCare Solutions',
                'policy_code'          => 'POL-PRIMECARE',
                'plan_type'            => 'SHARED',
                'coverage_type'        => 'ALL_PRINCIPAL',
                'coverage_period_type' => 'ACCOUNT',
                'mbl_type'             => 'Procedural',
                'mbl_amount'           => null,
                'endorsement_type'     => 'NEW',
                'endorsement_status'   => 'APPROVED',
                'account_status'       => 'active',
                'effective_date'       => now()->subMonths(2)->toDateString(),
                'expiration_date'      => now()->addMonths(10)->toDateString(),
            ],
            [
                'company_name'         => 'SunShield Benefits',
                'policy_code'          => 'POL-SUNSHIELD',
                'plan_type'            => 'SHARED',
                'coverage_type'        => 'ALL_DEPENDENT',
                'coverage_period_type' => 'ACCOUNT',
                'mbl_type'             => 'Fixed',
                'mbl_amount'           => 30000,
                'endorsement_type'     => 'NEW',
                'endorsement_status'   => 'APPROVED',
                'account_status'       => 'active',
                'effective_date'       => now()->subMonths(1)->toDateString(),
                'expiration_date'      => now()->addMonths(11)->toDateString(),
            ],
        ];

        $services = Service::all();

        foreach ($accounts as $data) {
            $account = Account::firstOrCreate(
                ['policy_code' => $data['policy_code']],
                array_merge($data, [
                    'hip_id'     => $hip->id,
                    'card_used'  => 'CARD-' . strtoupper(substr($data['policy_code'], 4, 6)),
                    'created_by' => 1,
                ])
            );

            if ($account->wasRecentlyCreated) {
                $this->attachServices($account, $services);
                $this->command->info("✅ Account created: {$account->policy_code} ({$account->plan_type} / {$account->coverage_type})");
            }
        }
    }

    private function attachServices(Account $account, $services): void
    {
        foreach ($services as $service) {
            $isUnlimited = $service->type === 'basic';
            AccountService::create([
                'account_id'       => $account->id,
                'service_id'       => $service->id,
                'is_unlimited'     => $isUnlimited,
                'quantity'         => $isUnlimited ? null : ($service->type === 'special' ? 2 : 3),
                'default_quantity' => $isUnlimited ? null : ($service->type === 'special' ? 2 : 3),
            ]);
        }
    }
}
