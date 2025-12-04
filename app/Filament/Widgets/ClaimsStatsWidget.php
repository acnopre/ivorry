<?php

namespace App\Filament\Widgets;

use App\Models\Dentist;
use App\Models\Procedure;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClaimsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $procedureQuery = Procedure::get();
        return [

            // PROCEDURES
            Stat::make('Total Procedures', $procedureQuery->count())
                ->icon('heroicon-o-document-check')
                ->color('primary')
                ->description('All procedures recorded'),

            Stat::make('Completed Procedures', $procedureQuery->where('status', 'completed')->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Procedures marked as completed'),

            Stat::make('Pending Procedures', $procedureQuery->where('status', 'pending')->count())
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Procedures still pending'),

            Stat::make('Invalid Procedures', $procedureQuery->where('status', 'invalid')->count())
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->description('Procedures marked invalid'),


            Stat::make('Returned Procedures', $procedureQuery->where('status', Procedure::STATUS_RETURN)->count())
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->description('Procedures marked returned'),

        ];
    }
}
