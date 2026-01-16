<?php

namespace App\Filament\Resources\ImportLogResource\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ImportLogStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $record = $this->getRecord();

        return [
            Stat::make('Total Rows', $record->total_rows),
            Stat::make('Success', $record->success_rows)
                ->color('success'),
            Stat::make('Errors', $record->error_rows)
                ->color('danger'),
            Stat::make('Status', strtoupper($record->status))
                ->color(match ($record->status) {
                    'completed' => 'success',
                    'partial'   => 'warning',
                    'failed'    => 'danger',
                    default     => 'primary',
                }),
        ];
    }
}
