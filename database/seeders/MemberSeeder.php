<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $memberUsers = [
            'member@example.com' => [
                'company' => 'Demo Healthcare Corp',
                'policy' => 'DEMO-2024-001',
                'first_name' => 'Juliana',
                'last_name' => 'Saw',
            ],
            'ivorry.member@example.com' => [
                'company' => 'Ivorry Healthcare Inc',
                'policy' => 'IVORRY-2024-001',
                'first_name' => 'Ivorry',
                'last_name' => 'Member',
            ],
        ];

        foreach ($memberUsers as $email => $data) {
            $user = User::where('email', $email)->first();

            if (!$user) continue;

            $account = Account::firstOrCreate(
                ['policy_code' => $data['policy']],
                [
                    'company_name' => $data['company'],
                    'hip_id' => 1,
                    'effective_date' => now(),
                    'expiration_date' => now()->addYear(),
                    'account_status' => 'active',
                    'plan_type' => 'INDIVIDUAL',
                    'coverage_period_type' => 'ACCOUNT',
                    'mbl_type' => 'Fixed',
                    'endorsement_status' => 'APPROVED',
                    'mbl_amount' => 50000,
                ]
            );

            if (!$user->member) {
                Member::create([
                    'account_id' => $account->id,
                    'user_id' => $user->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'member_type' => 'Principal',
                    'card_number' => 'CARD-' . strtoupper(substr($data['first_name'], 0, 3)) . '-' . rand(1000, 9999),
                    'birthdate' => now()->subYears(30),
                    'gender' => 'Female',
                    'email' => $email,
                    'effective_date' => now(),
                    'expiration_date' => now()->addYear(),
                    'status' => 'active',
                ]);
            }

            // Attach services to account if not already attached
            if (DB::table('account_service')->where('account_id', $account->id)->count() === 0) {
                $this->attachServices($account->id);
            }

            $this->command->info("✅ Member data created for {$email}");
        }
    }

    private function attachServices(int $accountId): void
    {
        $basicServices = DB::table('services')->where('type', 'basic')->pluck('id');
        $enhancementServices = DB::table('services')->where('type', 'enhancement')->pluck('id');
        $specialServices = DB::table('services')->where('type', 'special')->pluck('id');

        foreach ($basicServices as $serviceId) {
            DB::table('account_service')->insert([
                'account_id' => $accountId,
                'service_id' => $serviceId,
                'is_unlimited' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($enhancementServices as $serviceId) {
            DB::table('account_service')->insert([
                'account_id' => $accountId,
                'service_id' => $serviceId,
                'default_quantity' => 3,
                'quantity' => 3,
                'is_unlimited' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($specialServices as $serviceId) {
            DB::table('account_service')->insert([
                'account_id' => $accountId,
                'service_id' => $serviceId,
                'default_quantity' => 2,
                'quantity' => 2,
                'is_unlimited' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
