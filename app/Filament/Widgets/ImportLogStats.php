<?php

namespace App\Filament\Widgets;

use App\Models\ImportLog;
use Filament\Infolists\Components\Card;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ImportLogStats extends StatsOverviewWidget
{

    protected function getCards(): array
    {
        return [
            Stat::make('Total Imports', ImportLog::count())
                ->description('All import attempts')
                ->color('primary'),

            Stat::make('Successful', ImportLog::where('status', 'completed')->count())
                ->description('Successfully completed imports')
                ->color('success'),

            Stat::make('Partial', ImportLog::where('status', 'partial')->count())
                ->description('Imports with some errors')
                ->color('warning'),

            Stat::make('Errors', ImportLog::where('status', 'error')->count())
                ->description('Failed imports')
                ->color('danger'),
        ];
    }
}
