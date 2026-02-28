<?php

namespace Tests\Unit\Models;

use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_completed_returns_true_for_completed_status(): void
    {
        $log = ImportLog::create([
            'filename' => 'test.xlsx',
            'user_id' => User::factory()->create()->id,
            'status' => 'completed',
        ]);

        $this->assertTrue($log->isCompleted());
    }

    public function test_is_completed_returns_true_for_partial_status(): void
    {
        $log = ImportLog::create([
            'filename' => 'test.xlsx',
            'user_id' => User::factory()->create()->id,
            'status' => 'partial',
        ]);

        $this->assertTrue($log->isCompleted());
    }

    public function test_is_completed_returns_false_for_processing_status(): void
    {
        $log = ImportLog::create([
            'filename' => 'test.xlsx',
            'user_id' => User::factory()->create()->id,
            'status' => 'processing',
        ]);

        $this->assertFalse($log->isCompleted());
    }
}
