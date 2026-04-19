<?php

namespace App\Jobs;

use App\Imports\AccountImport;
use App\Models\ImportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ProcessAccountImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 0;
    public int $tries = 1;

    public function __construct(
        public string $filePath,
        public int $importLogId,
        public int $userId,
        public bool $migrationMode = false
    ) {}

    public function handle(): void
    {
        $log = ImportLog::findOrFail($this->importLogId);

        try {
            Excel::import(
                new AccountImport($log, $this->userId, $this->migrationMode),
                $this->filePath
            );
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed']);
            \Log::error('Account import job failed', ['message' => $e->getMessage()]);
        }
    }

    public function failed(\Throwable $e): void
    {
        ImportLog::find($this->importLogId)?->update(['status' => 'failed']);
        \Log::error('Account import job failed', ['message' => $e->getMessage()]);
    }
}
