<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AccountStatsWidget extends BaseWidget
{

    protected function getStats(): array
    {
        $baseQuery = Account::query();
        $pendingEndorsements = $baseQuery->clone()->where('endorsement_status', 'PENDING');
        $todayAccounts = Account::whereDate('created_at', today());

        return [
            Stat::make('Pending Endorsements', $pendingEndorsements->count())
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Requires your approval')
                ->chart([5, 8, 6, 10, 7, $pendingEndorsements->count()]),

            Stat::make('New Accounts Today', $todayAccounts->count())
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->description('Created today'),

            Stat::make('Active Accounts', $baseQuery->clone()->where('account_status', 'active')->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Currently active')
                ->chart([45, 50, 48, 55, 52, 60, $baseQuery->clone()->where('account_status', 'active')->count()]),

            Stat::make('Pending Renewals', $baseQuery->clone()->where('endorsement_type', 'RENEWAL')->where('endorsement_status', 'PENDING')->count())
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->description('Awaiting renewal approval'),

            Stat::make('Pending Amendments', $baseQuery->clone()->where('endorsement_type', 'AMENDMENT')->where('endorsement_status', 'PENDING')->count())
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->description('Awaiting amendment approval'),

            Stat::make('Expiring Soon', $baseQuery->clone()->where('account_status', 'active')->whereBetween('expiration_date', [now(), now()->addDays(30)])->count())
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->description('Expiring within 30 days'),

            Stat::make('Fixed MBL Accounts', $baseQuery->clone()->where('mbl_type', 'Fixed')->where('account_status', 'active')->count())
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->description('Using fixed MBL'),

            Stat::make('Total MBL Balance', '₱' . number_format($baseQuery->clone()->where('mbl_type', 'Fixed')->sum('mbl_balance'), 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('primary')
                ->description('Combined fixed MBL balance'),
        ];
    }


    // public static function canView(): bool
    // {
    //     return auth()->check()
    //         && auth()->user()->hasAnyRole(['Super Admin', 'Upper Management', 'Account Management']);
    // }
}
