<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = DB::table('accounts')->pluck('id');

        $basicServices = DB::table('services')
            ->where('type', 'basic')
            ->pluck('id');

        $enhancementServices = DB::table('services')
            ->where('type', 'enhancement')
            ->pluck('id');

        $specialServices = DB::table('services')
            ->where('type', 'special')
            ->pluck('id');

        foreach ($accounts as $accountId) {
            // ✅ Assign all basic services (unlimited)
            foreach ($basicServices as $serviceId) {
                DB::table('account_service')->insert([
                    'account_id'   => $accountId,
                    'service_id'   => $serviceId,
                    'remarks'      => 'Basic service — covered by policy ' . strtoupper(Str::random(4)),
                    'is_unlimited' => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            // 🎲 Randomly assign enhancement services (limited)
            // $assignedEnhancements = $enhancementServices->shuffle()->take(rand(3, 7));
            $quantity = rand(1, 5);
            foreach ($enhancementServices as $serviceId) {
                DB::table('account_service')->insert([
                    'account_id'   => $accountId,
                    'service_id'   => $serviceId,
                    'default_quantity' => $quantity,
                    'quantity'     => $quantity,
                    'remarks'      => rand(0, 1) ? 'Enhancement coverage under plan ' . strtoupper(Str::random(3)) : null,
                    'is_unlimited' => false,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            $quantity = rand(1, 5);
            foreach ($specialServices as $serviceId) {
                DB::table('account_service')->insert([
                    'account_id'   => $accountId,
                    'service_id'   => $serviceId,
                    'default_quantity' => $quantity,
                    'quantity'     => $quantity,
                    'remarks'      => rand(0, 1) ? 'Special coverage under plan ' . strtoupper(Str::random(3)) : null,
                    'is_unlimited' => false,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }
}
