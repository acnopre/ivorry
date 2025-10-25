<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Procedure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProcedureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Get all service IDs from the database
        $serviceIds = DB::table('services')->pluck('id')->toArray();

        if (empty($serviceIds)) {
            $this->command->warn('⚠️ No services found. Please run ServicesSeeder first.');
            return;
        }

        foreach ([1, 2, 3] as $memberId) {
            for ($i = 1; $i <= 3; $i++) {
                Procedure::create([
                    'member_id' => $memberId,
                    'clinic_id' => rand(1, 3),
                    'service_id' => $serviceIds[array_rand($serviceIds)],
                    'availment_date' => Carbon::now()->subDays(rand(1, 15)),
                    'approval_code' => Str::upper(Str::random(9)),
                    'status' => Procedure::STATUS_PENDING,
                ]);
            }
        }

        $this->command->info('✅ 9 Procedures (3 per member) successfully seeded!');
    }
}
