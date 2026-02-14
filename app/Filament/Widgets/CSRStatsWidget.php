<?php

namespace App\Filament\Widgets;

use App\Models\{Account, Member, Procedure, Role};
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CSRStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active Accounts', Account::where('account_status', 'active')->count())
                ->icon('heroicon-o-briefcase')
                ->color('success')
                ->description('Currently active accounts'),

            Stat::make('Active Members', Member::where('status', 'active')->count())
                ->icon('heroicon-o-users')
                ->color('success')
                ->description('Currently active members'),

            Stat::make('Pending Procedures', Procedure::where('status', 'pending')->count())
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Awaiting dentist signature'),

            Stat::make('Sign Procedures', Procedure::where('status', 'sign')->count())
                ->icon('heroicon-o-pencil-square')
                ->color('info')
                ->description('Ready for processing'),

            Stat::make('Returned Procedures', Procedure::where('status', 'returned')->count())
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->description('Needs resubmission'),

            Stat::make('Total Procedures Today', Procedure::whereDate('created_at', today())->count())
                ->icon('heroicon-o-document-check')
                ->color('primary')
                ->description('Procedures created today'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole(Role::CSR);
    }
}
