<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\{Account, Member, Clinic, Dentist, Procedure};

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Accounts', Account::count())
                ->description('Registered company accounts')
                ->icon('heroicon-o-briefcase')
                ->color('primary'),

            Stat::make('Members', Member::count())
                ->description('Total enrolled members')
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('Clinics', Clinic::count())
                ->description('Partner dental clinics')
                ->icon('heroicon-o-building-office')
                ->color('info'),

            Stat::make('Dentists', Dentist::count())
                ->description('Registered practitioners')
                ->icon('heroicon-o-user')
                ->color('warning'),

            Stat::make('Approved Procedures', Procedure::where('status', 'approve')->count())
                ->description('Procedures approved by dentists')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('success'),

            Stat::make('Completed Procedures', Procedure::where('status', 'completed')->count())
                ->description('Approved and finished services')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Valid Procedures', Procedure::where('status', 'valid')->count())
                ->description('Validated procedure claims')
                ->icon('heroicon-o-check')
                ->color('success'),

            Stat::make('Declined Procedures', Procedure::where('status', 'invalid')->count())
                ->description('Procedures marked as invalid')
                ->icon('heroicon-o-x-mark')
                ->color('danger'),

            Stat::make('Returned Procedures', Procedure::where('status', 'return')->count())
                ->description('Procedures sent back for review')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning'),
        ];
    }
    public static function canView(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole(['Super Admin', 'Upper Management']);
    }
}
