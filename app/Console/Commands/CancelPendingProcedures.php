<?php

namespace App\Console\Commands;

use App\Models\Procedure;
use App\Services\ProcedureService;
use Illuminate\Console\Command;

class CancelPendingProcedures extends Command
{
    protected $signature   = 'procedures:cancel-pending';
    protected $description = 'Cancel all pending procedures whose availment_date has passed, returning quantity and MBL';

    public function handle(): int
    {
        $procedures = Procedure::with(['member.account', 'units'])
            ->where('status', Procedure::STATUS_PENDING)
            ->whereDate('availment_date', '<', today())
            ->get();

        $count  = 0;
        $failed = 0;

        foreach ($procedures as $procedure) {
            try {
                $cancelled = ProcedureService::cancel(
                    $procedure,
                    'Auto-cancelled: procedure was not processed by end of day.',
                    true
                );

                if ($cancelled) {
                    $count++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        $this->info("{$count} pending procedure(s) cancelled, {$failed} failed/skipped.");

        return self::SUCCESS;
    }
}
