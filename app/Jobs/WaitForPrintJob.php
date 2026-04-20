<?php

namespace App\Jobs;

use App\Models\GeneratedSoa;
use App\Models\Procedure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WaitForPrintJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes max
    public int $tries   = 1;

    public function __construct(
        public ?string $jobId,
        public int     $soaId,
        public string  $printerName,
        public string  $sequenceNumber
    ) {}

    public function handle(): void
    {
        $soa = GeneratedSoa::with('procedures')->find($this->soaId);
        if (!$soa) return;

        // If no job ID was captured, mark processed immediately
        if (!$this->jobId) {
            $this->markProcessed($soa);
            return;
        }

        $maxAttempts = 120; // poll every 5s for up to 10 minutes

        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(5);

            $output = [];
            exec('lpstat -l -j ' . escapeshellarg($this->jobId) . ' 2>&1', $output);
            $statusText = trim(implode(' ', $output));

            Log::info('WaitForPrintJob poll', [
                'attempt'    => $i + 1,
                'job'        => $this->jobId,
                'soa'        => $this->soaId,
                'statusText' => $statusText,
            ]);

            // Job gone from queue = completed successfully
            if (empty($statusText)) {
                $this->markProcessed($soa);
                return;
            }

            // Hard failures — stop immediately
            foreach (['offline', 'stopped', 'unable', 'error', 'aborted', 'canceled'] as $indicator) {
                if (stripos($statusText, $indicator) !== false) {
                    $this->markFailed($soa);
                    return;
                }
            }

            // Still spooling/printing — continue polling
        }

        // Timed out
        Log::warning('WaitForPrintJob timed out', ['job' => $this->jobId, 'soa' => $this->soaId]);
        $this->markFailed($soa);
    }

    private function markProcessed(GeneratedSoa $soa): void
    {
        $soa->update(['status' => 'processed']);

        Procedure::whereIn('id', $soa->procedures->pluck('id'))
            ->update(['status' => 'processed']);

        Log::info('Print job completed — marked processed', [
            'job' => $this->jobId,
            'soa' => $this->soaId,
        ]);
    }

    private function markFailed(GeneratedSoa $soa): void
    {
        $soa->update(['status' => 'print_failed']);

        Log::warning('Print job failed — marked print_failed', [
            'job' => $this->jobId,
            'soa' => $this->soaId,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        GeneratedSoa::find($this->soaId)?->update(['status' => 'print_failed']);
        Log::error('WaitForPrintJob exception', ['message' => $e->getMessage(), 'soa' => $this->soaId]);
    }
}
