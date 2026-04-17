<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('members:deactivate')->dailyAt('00:05');
Schedule::command('accounts:activate')->dailyAt('00:05');
// Schedule::command('members:deactivate')->everyMinute(); // uncomment for local testing
// Schedule::command('accounts:activate')->everyMinute(); // uncomment for local testing
