<?php

namespace App\Filament\Widgets;

use App\Models\{Account, Member, Procedure, Role};
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CSRStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayProcedures = Procedure::whereDate('created_at', today());

        $stats = [
            Stat::make('Active Accounts', Account::where('account_status', 'active')->count())
                ->icon('heroicon-o-briefcase')
                ->color('success')
                ->description('Currently active')
                ->chart([45, 50, 48, 55, 52, 60, Account::where('account_status', 'active')->count()]),

            Stat::make('Active Members', Member::where('status', 'active')->count())
                ->icon('heroicon-o-users')
                ->color('success')
                ->description('Currently active members'),

            Stat::make('Today\'s Procedures', $todayProcedures->count())
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->description('Created today')
                ->chart([5, 8, 6, 10, 7, 12, $todayProcedures->count()]),

            Stat::make('Pending Signature', Procedure::where('status', 'pending')->count())
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Awaiting dentist signature'),

            Stat::make('Signed Today', Procedure::where('status', 'signed')->whereDate('updated_at', today())->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Completed today'),

            Stat::make('Ready for Processing', Procedure::where('status', 'signed')->count())
                ->icon('heroicon-o-document-check')
                ->color('info')
                ->description('Awaiting validation'),
        ];


        return $stats;
    }

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole(Role::CSR);
    }
}
