<?php

namespace App\Console\Commands;

use App\Models\Procedure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelPendingProcedures extends Command
{
    protected $signature   = 'procedures:cancel-pending';
    protected $description = 'Cancel all pending procedures at end of day';

    public function handle(): int
    {
        DB::beginTransaction();
        try {
            $count = Procedure::where('status', Procedure::STATUS_PENDING)
                ->whereDate('created_at', today())
                ->update([
                    'status'          => Procedure::STATUS_CANCELLED,
                    'previous_status' => Procedure::STATUS_PENDING,
                    'remarks'         => 'Auto-cancelled: procedure was not processed by end of day.',
                ]);

            DB::commit();

            Log::info("procedures:cancel-pending — {$count} procedure(s) cancelled.");
            $this->info("{$count} pending procedure(s) cancelled.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('procedures:cancel-pending failed', ['message' => $e->getMessage()]);
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
