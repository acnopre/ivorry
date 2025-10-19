<?php

namespace App\Filament\Widgets;

use App\Models\Unit;
use App\Models\UnitType;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Unit Types', UnitType::count())
                ->description('Types of units (Quadrant, Tooth, etc.)')
                ->descriptionIcon('heroicon-m-tag')
                ->color('primary'),

            Stat::make('Total Units', Unit::count())
                ->description('Registered unit entries')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),

            Stat::make('Recently Added', Unit::latest()->take(5)->count())
                ->description('Units added recently')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
