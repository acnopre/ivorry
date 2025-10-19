<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\ActivityTimeline;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\RecentClaimsTable;
use Filament\Notifications\Notification;

class Dashboard extends BaseDashboard
{
    public function mount(): void
    {
        if (session('password_updated')) {
            Notification::make()
                ->success()
                ->title('Welcome!')
                ->body('Your password has been updated successfully.')
                ->send();
        }
    }
    public function getWidgets(): array
    {
        return [
            DashboardStats::class,
            RecentClaimsTable::class,
            ActivityTimeline::class,
        ];
    }
}
