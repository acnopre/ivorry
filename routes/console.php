<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\ScheduleSetting;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

// Always run heartbeat to confirm scheduler is active
Schedule::command('scheduler:heartbeat')->everyMinute();
// Load schedule settings from DB, fall back to defaults if table doesn't exist yet
try {
    $settings = ScheduleSetting::all()->keyBy('command');

    foreach ($settings as $command => $setting) {
        if (!$setting->enabled) continue;

        $event = $setting->isEveryMinute()
            ? Schedule::command($command)->everyMinute()
            : Schedule::command($command)->dailyAt($setting->daily_time);

        $event->onSuccess(function () use ($setting) {
            $setting->update(['last_run_at' => now(), 'last_run_status' => 'success']);
        })->onFailure(function () use ($setting) {
            $setting->update(['last_run_at' => now(), 'last_run_status' => 'failed']);
        });
    }
} catch (\Throwable $e) {
    // Fallback to hardcoded defaults if DB is unavailable (e.g. during initial setup)
    Schedule::command('members:deactivate')->dailyAt('00:05');
    Schedule::command('accounts:activate')->dailyAt('00:05');
    Schedule::command('fees:apply-approved')->dailyAt('00:05');
    Schedule::command('print:check-jobs')->everyMinute();
}
