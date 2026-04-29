<?php

namespace App\Filament\Pages;

use App\Models\Role;
use App\Models\ScheduleSetting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class SchedulerSettings extends Page
{
    protected static ?string $title = 'Scheduler Settings';
    protected static string $view = 'filament.pages.scheduler-settings';
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 99;

    public array $settings = [];

    public function mount(): void
    {
        $this->settings = ScheduleSetting::all()
            ->mapWithKeys(fn($s) => [$s->id => [
                'enabled'    => $s->enabled,
                'daily_time' => $s->daily_time,
            ]])
            ->toArray();
    }

    public function save(): void
    {
        foreach ($this->settings as $id => $data) {
            ScheduleSetting::where('id', $id)->update([
                'enabled'    => $data['enabled'] ?? false,
                'daily_time' => $data['daily_time'] ?? null,
            ]);
        }

        Notification::make()->title('Scheduler settings saved.')->success()->send();
    }

    public function runNow(int $id): void
    {
        $setting = ScheduleSetting::find($id);
        if (!$setting) return;

        try {
            Artisan::call($setting->command);
            $setting->update(['last_run_at' => now(), 'last_run_status' => 'success']);
            Notification::make()->title("'{$setting->label}' ran successfully.")->success()->send();
        } catch (\Throwable $e) {
            $setting->update(['last_run_at' => now(), 'last_run_status' => 'failed']);
            Notification::make()->title("'{$setting->label}' failed.")->body($e->getMessage())->danger()->send();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole([Role::UPPER_MANAGEMENT, Role::MIDDLE_MANAGEMENT, Role::SUPER_ADMIN]);
    }
}
