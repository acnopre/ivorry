<?php

namespace App\Jobs;

use App\Imports\MembersImport;
use App\Models\ImportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ProcessMemberImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 0;
    public int $tries = 1;

    public function __construct(
        public string $filePath,
        public string $filename,
        public int $userId
    ) {}

    public function handle(): void
    {
        try {
            $import = new MembersImport($this->filename, $this->userId);
            Excel::import($import, $this->filePath);
        } catch (\Throwable $e) {
            \Log::error('Member import job failed', ['message' => $e->getMessage()]);
        }
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('Member import job failed', ['message' => $e->getMessage()]);
    }
}
