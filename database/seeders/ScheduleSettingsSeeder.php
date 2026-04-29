<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = [
            [
                'command'     => 'members:deactivate',
                'label'       => 'Deactivate Members',
                'description' => 'Deactivates members whose inactive_date or expiration_date has passed.',
                'daily_time'  => '00:05',
                'enabled'     => true,
            ],
            [
                'command'     => 'accounts:activate',
                'label'       => 'Activate Accounts',
                'description' => 'Activates accounts and their members when the effective_date is reached.',
                'daily_time'  => '00:05',
                'enabled'     => true,
            ],
            [
                'command'     => 'fees:apply-approved',
                'label'       => 'Apply Approved Fees',
                'description' => 'Applies approved service fees whose effective date has arrived.',
                'daily_time'  => '00:05',
                'enabled'     => true,
            ],
            [
                'command'     => 'procedures:cancel-pending',
                'label'       => 'Cancel Pending Procedures',
                'description' => 'Auto-cancels all pending procedures whose availment_date has passed.',
                'daily_time'  => '23:59',
                'enabled'     => true,
            ],
            [
                'command'     => 'print:check-jobs',
                'label'       => 'Check Print Jobs',
                'description' => 'Polls CUPS every minute for pending print jobs and updates SOA/procedure status.',
                'daily_time'  => null,
                'enabled'     => true,
            ],
        ];

        foreach ($tasks as $task) {
            DB::table('schedule_settings')->updateOrInsert(
                ['command' => $task['command']],
                array_merge($task, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
