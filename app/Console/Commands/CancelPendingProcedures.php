<?php

namespace App\Console\Commands;

use App\Models\Procedure;
use App\Services\ProcedureService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
                    true // treat as CSR so both pending and signed are eligible
                );

                if ($cancelled) {
                    $count++;
                } else {
                    $failed++;
                    Log::warning("procedures:cancel-pending — skipped procedure #{$procedure->id} (status: {$procedure->status})");
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error("procedures:cancel-pending — failed on procedure #{$procedure->id}", ['message' => $e->getMessage()]);
            }
        }

        Log::info("procedures:cancel-pending — {$count} cancelled, {$failed} failed/skipped.");
        $this->info("{$count} pending procedure(s) cancelled, {$failed} failed/skipped.");

        return self::SUCCESS;
    }
}
