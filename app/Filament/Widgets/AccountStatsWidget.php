<?php

namespace App\Filament\Widgets;

use App\Models\{Account, Role};
use App\Filament\Resources\AccountResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $q = Account::query();

        return [
            Stat::make('New Accounts Today', Account::whereDate('created_at', today())->count())
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->description('Created today')
                ->url(AccountResource::getUrl('index')),

            Stat::make('Active Accounts', $q->clone()->where('account_status', 'active')->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Currently active')
                ->chart([45, 50, 48, 55, 52, 60, $q->clone()->where('account_status', 'active')->count()])
                ->url(AccountResource::getUrl('index', ['tableFilter' => 'account_status:active'])),

            Stat::make('Pending Renewals', $q->clone()->where('endorsement_type', 'RENEWAL')->where('endorsement_status', 'PENDING')->count())
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->description('Awaiting renewal approval')
                ->url(AccountResource::getUrl('index', ['tableFilter' => 'endorsement_status:PENDING,endorsement_type:RENEWAL'])),

            Stat::make('Pending Amendments', $q->clone()->where('endorsement_type', 'AMENDMENT')->where('endorsement_status', 'PENDING')->count())
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->description('Awaiting amendment approval')
                ->url(AccountResource::getUrl('index', ['tableFilter' => 'endorsement_status:PENDING,endorsement_type:AMENDMENT'])),

            Stat::make('Expiring Soon', $q->clone()->where('account_status', 'active')->whereBetween('expiration_date', [now(), now()->addDays(30)])->count())
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->description('Expiring within 30 days')
                ->url(AccountResource::getUrl('index', ['tableFilter' => 'account_status:active'])),

            Stat::make('Fixed MBL Accounts', $q->clone()->where('mbl_type', 'Fixed')->where('account_status', 'active')->count())
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->description('Using fixed MBL')
                ->url(AccountResource::getUrl('index', ['tableFilter' => 'account_status:active'])),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT, Role::MIDDLE_MANAGEMENT, Role::ACCOUNT_MANAGER]);
    }
}
