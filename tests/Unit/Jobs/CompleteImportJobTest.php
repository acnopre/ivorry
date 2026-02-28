<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CompleteImportJob;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteImportJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_status_to_completed_when_no_errors(): void
    {
        $user = User::factory()->create();
        $log = ImportLog::create([
            'filename' => 'test.xlsx',
            'user_id' => $user->id,
            'status' => 'processing',
            'success_rows' => 10,
            'error_rows' => 0,
        ]);

        $job = new CompleteImportJob($log);
        $job->handle();

        $this->assertEquals('completed', $log->fresh()->status);
    }

    public function test_updates_status_to_partial_when_errors_exist(): void
    {
        $user = User::factory()->create();
        $log = ImportLog::create([
            'filename' => 'test.xlsx',
            'user_id' => $user->id,
            'status' => 'processing',
            'success_rows' => 5,
            'error_rows' => 3,
        ]);

        $job = new CompleteImportJob($log);
        $job->handle();

        $this->assertEquals('partial', $log->fresh()->status);
    }
}
