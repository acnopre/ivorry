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
        // Define the base query for the Account model
        $baseQuery = Account::query();

        // Define reusable scopes for clarity and efficiency
        $renewalQuery = fn(Builder $query) => $query->where('endorsement_type', 'RENEWAL');
        $amendmentQuery = fn(Builder $query) => $query->where('endorsement_type', 'AMENDMENT');
        $pendingQuery = fn(Builder $query) => $query->where('endorsement_status', 'PENDING');
        $approvedQuery = fn(Builder $query) => $query->where('endorsement_status', 'APPROVED');

        return [
            Stat::make('New Accounts', $baseQuery->clone()->where('endorsement_type', 'NEW')->count())
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->description('Newly created accounts'),

            Stat::make('Renewed Accounts', $baseQuery->clone()->when($renewalQuery)->when($approvedQuery)->count())
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->description('Accounts approved for renewal'),

            Stat::make('Renewal Accounts Pending', $baseQuery->clone()->when($renewalQuery)->when($pendingQuery)->count())
                ->icon('heroicon-o-arrow-path')
                ->color('warning') // Use warning for pending actions
                ->description('Accounts awaiting renewal approval'),

            Stat::make('Amended Accounts', $baseQuery->clone()->when($amendmentQuery)->when($approvedQuery)->count())
                ->icon('heroicon-o-pencil-square')
                ->color('info') // Use info for a completed action (amendment)
                ->description('Accounts with approved amendments'),

            Stat::make('Amendment Accounts Pending', $baseQuery->clone()->when($amendmentQuery)->when($pendingQuery)->count())
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->description('Accounts awaiting amendment approval'),

            Stat::make('Active Accounts', $baseQuery->clone()->where('account_status', 1)->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Accounts currently active'),

            Stat::make('Inactive Accounts', $baseQuery->clone()->where('account_status', 0)->count())
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->description('Accounts currently inactive'),

            Stat::make('Total Pending Endorsements', $baseQuery->clone()->when($pendingQuery)->count())
                ->icon('heroicon-o-clock') // Better icon for pending
                ->color('warning')
                ->description('Total accounts awaiting any endorsement decision'),
        ];
    }


    // public static function canView(): bool
    // {
    //     return auth()->check()
    //         && auth()->user()->hasAnyRole(['Super Admin', 'Upper Management', 'Account Management']);
    // }
}
