<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AccountStatsWidget;
use App\Filament\Widgets\AccreditationStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\ActivityTimeline;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\RecentClaimsTable;
use App\Models\Role;
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
        if (auth()->user()?->hasAnyRole(Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT)) {
            return [
                DashboardStats::class,
                AccountStatsWidget::class,
                RecentClaimsTable::class,
                ActivityTimeline::class,
            ];
        } else if (auth()->user()?->hasAnyRole(Role::ACCOUNT_MANAGER)) {
            return [
                AccountStatsWidget::class,
            ];
        } else if (auth()->user()?->hasAnyRole(Role::CLAIMS_PROCESSOR)) {
            return [
                RecentClaimsTable::class,
            ];
        } else if (auth()->user()?->hasAnyRole(Role::ACCREDITATION)) {
            return [
                AccreditationStatsWidget::class,
            ];
        } else {
            return [];
        }
    }
}
