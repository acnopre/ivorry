<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleSetting extends Model
{
    protected $fillable = [
        'command',
        'label',
        'description',
        'daily_time',
        'enabled',
        'last_run_at',
        'last_run_status',
    ];

    protected $casts = [
        'enabled'     => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public static function forCommand(string $command): ?self
    {
        return static::where('command', $command)->first();
    }

    public function isEveryMinute(): bool
    {
        return is_null($this->daily_time);
    }

    public function scheduleLabel(): string
    {
        return $this->isEveryMinute() ? 'Every minute' : 'Daily at ' . $this->daily_time;
    }
}
