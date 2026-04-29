<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SchedulerHeartbeat extends Command
{
    protected $signature   = 'scheduler:heartbeat';
    protected $description = 'Pings a cache key every minute to confirm the scheduler is running';

    public function handle(): int
    {
        Cache::put('scheduler_last_ping', now()->toDateTimeString(), now()->addMinutes(5));
        return self::SUCCESS;
    }
}
