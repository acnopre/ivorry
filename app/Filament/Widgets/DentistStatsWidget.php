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
        $todayProcedures = Procedure::where('clinic_id', $clinicId)->whereDate('created_at', today());
        $thisMonthProcedures = Procedure::where('clinic_id', $clinicId)->whereMonth('created_at', now()->month);

        return [
            Stat::make('Today\'s Procedures', $todayProcedures->count())
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->description('Created today')
                ->chart([3, 5, 4, 7, 6, 8, $todayProcedures->count()]),

            Stat::make('This Month', $thisMonthProcedures->count())
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->description('Procedures this month'),

            Stat::make('Pending Signature', Procedure::where('status', 'pending')->where('clinic_id', $clinicId)->count())
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Awaiting your signature')
                ->url(route('filament.admin.resources.procedures.index', ['tableFilters[status][value]' => 'pending'])),

            Stat::make('Signed Today', Procedure::where('status', 'sign')->where('clinic_id', $clinicId)->whereDate('updated_at', today())->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Completed today'),

            Stat::make('Total Signed', Procedure::where('status', 'sign')->where('clinic_id', $clinicId)->count())
                ->icon('heroicon-o-document-check')
                ->color('success')
                ->description('All signed procedures'),

            Stat::make('Returned', Procedure::where('status', 'returned')->where('clinic_id', $clinicId)->count())
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->description('Needs attention'),
        ];
    }
}
