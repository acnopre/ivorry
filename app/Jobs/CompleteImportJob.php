<?php

namespace App\Jobs;

use App\Models\ImportLog;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CompleteImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ImportLog $log)
    {
    }

    public function handle(): void
    {
        $this->log->refresh();
        
        $this->log->update([
            'status' => $this->log->error_rows > 0 ? 'partial' : 'completed',
        ]);
        
        Log::info("Import completed for log ID: {$this->log->id}");
        
        $message = "Accounts import completed! {$this->log->success_rows} accounts imported.";
        if ($this->log->skipped_rows > 0) {
            $message .= " {$this->log->skipped_rows} rows skipped.";
        }
        if ($this->log->error_rows > 0) {
            $message .= " {$this->log->error_rows} rows failed.";
        }

        Notification::make()
            ->title($message)
            ->success()
            ->sendToDatabase(\App\Models\User::find($this->log->user_id));
    }
}
