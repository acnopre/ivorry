<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use App\Models\Claim;
use App\Models\Soa;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active Members', Member::where('status', 'active')->count())
                ->description('Currently enrolled members')
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),

            Stat::make('Pending Claims', Claim::where('status', 'pending')->count())
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('warning'),

            Stat::make('SOAs Generated', Soa::count())
                ->description('Total Statements of Account')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('info'),
        ];
    }
}
