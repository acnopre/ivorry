<?php

namespace App\Filament\Widgets;

use App\Models\Dentist;
use App\Models\Procedure;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DentistStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $clinicId = Auth::user()->clinic->id;
        $procedureQuery = Procedure::get();

        return [

            // PROCEDURES
            Stat::make('Total Procedures', $procedureQuery->where('clinic_id', $clinicId)->count())
                ->icon('heroicon-o-document-check')
                ->color('primary')
                ->description('All procedures recorded'),

            Stat::make('Completed Procedures', $procedureQuery->where('status', 'completed')->where('clinic_id', $clinicId)->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Procedures marked as completed'),

            Stat::make('Pending Procedures', $procedureQuery->where('status', 'pending')->where('clinic_id', $clinicId)->count())
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Procedures still pending'),

            Stat::make('Invalid Procedures', $procedureQuery->where('status', 'invalid')->where('clinic_id', $clinicId)->count())
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->description('Procedures marked invalid'),


            Stat::make('Returned Procedures', $procedureQuery->where('status', 'returned')->where('clinic_id', $clinicId)->count())
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->description('Procedures marked returned'),

        ];
    }
}
