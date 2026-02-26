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
        $record = $this->record ?? null;
        
        if ($record) {
            return [
                Stat::make('Total Rows', $record->total_rows)
                    ->description('Total rows processed')
                    ->color('primary'),

                Stat::make('Success', $record->success_rows)
                    ->description('Successfully imported')
                    ->color('success'),

                Stat::make('Skipped', $record->skipped_rows)
                    ->description('Rows skipped (duplicates)')
                    ->color('warning'),

                Stat::make('Errors', $record->error_rows)
                    ->description('Failed rows')
                    ->color('danger'),
            ];
        }

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
