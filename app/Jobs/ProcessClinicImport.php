<?php

namespace App\Jobs;

use App\Imports\ClinicImport;
use App\Models\ImportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ProcessClinicImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 0;
    public int $tries = 1;

    public function __construct(
        public string $filePath,
        public int $importLogId
    ) {}

    public function handle(): void
    {
        $log = ImportLog::findOrFail($this->importLogId);

        try {
            Excel::import(new ClinicImport($log), $this->filePath);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed']);
            \Log::error('Clinic import job failed', ['message' => $e->getMessage()]);
        }
    }

    public function failed(\Throwable $e): void
    {
        ImportLog::find($this->importLogId)?->update(['status' => 'failed']);
        \Log::error('Clinic import job failed', ['message' => $e->getMessage()]);
    }
}
