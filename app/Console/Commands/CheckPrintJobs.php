<?php

namespace App\Console\Commands;

use App\Models\GeneratedSoa;
use App\Models\Procedure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckPrintJobs extends Command
{
    protected $signature   = 'print:check-jobs';
    protected $description = 'Poll CUPS for pending print jobs and update SOA/procedure status';

    public function handle(): int
    {
        // Get all print logs with a job ID that are still in 'sent' status
        $pendingLogs = DB::table('print_logs')
            ->whereNotNull('cups_job_id')
            ->where('status', 'sent')
            ->where('created_at', '>=', now()->subHours(2))
            ->get();

        if ($pendingLogs->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($pendingLogs as $log) {
            $output = [];
            exec('lpstat -o ' . escapeshellarg($log->cups_job_id) . ' 2>&1', $output);
            $statusText = trim(implode(' ', $output));

            Log::info('CheckPrintJobs poll', [
                'job'        => $log->cups_job_id,
                'soa'        => $log->document_id,
                'statusText' => $statusText,
            ]);

            // Job gone or not found = completed successfully
            if (
                empty($statusText) ||
                stripos($statusText, 'unknown') !== false ||
                stripos($statusText, 'invalid') !== false ||
                stripos($statusText, 'not found') !== false
            ) {
                $this->markCompleted($log);
                continue;
            }

            // Hard failure
            foreach (['offline', 'stopped', 'unable', 'error', 'aborted', 'canceled'] as $indicator) {
                if (stripos($statusText, $indicator) !== false) {
                    $this->markFailed($log);
                    break;
                }
            }

            // Still printing — leave as 'sent', will check again next minute
        }

        return self::SUCCESS;
    }

    private function markCompleted(object $log): void
    {
        DB::table('print_logs')->where('id', $log->id)->update(['status' => 'completed']);

        $soa = GeneratedSoa::with('procedures')->find($log->document_id);
        if (!$soa) return;

        $soa->update(['status' => 'processed']);

        Procedure::whereIn('id', $soa->procedures->pluck('id'))
            ->update(['status' => 'processed']);

        $this->info("Job {$log->cups_job_id} completed — SOA #{$log->document_id} marked processed.");
    }

    private function markFailed(object $log): void
    {
        DB::table('print_logs')->where('id', $log->id)->update(['status' => 'failed']);

        $soa = GeneratedSoa::with('procedures')->find($log->document_id);
        if (!$soa) return;

        $soa->update(['status' => 'print_failed']);

        // Revert procedures back to valid so they can be retried
        Procedure::whereIn('id', $soa->procedures->pluck('id'))
            ->update(['status' => 'valid']);

        $this->warn("Job {$log->cups_job_id} failed — SOA #{$log->document_id} marked print_failed, procedures reverted to valid.");
    }
}
