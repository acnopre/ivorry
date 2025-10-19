<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\{Account, Member, Clinics, Dentist, Claim, Procedure};

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

            Stat::make('Clinics', Clinics::count())
                ->description('Partner dental clinics')
                ->icon('heroicon-o-building-office')
                ->color('info'),

            Stat::make('Dentists', Dentist::count())
                ->description('Registered practitioners')
                ->icon('heroicon-o-user-circle')
                ->color('warning'),

            Stat::make('Pending Claims', Procedure::where('status', 'pending')->count())
                ->description('Claims awaiting approval')
                ->icon('heroicon-o-clock')
                ->color('danger'),

            Stat::make('Completed Procedures', Procedure::where('status', 'approved')->count())
                ->description('Approved and finished services')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
