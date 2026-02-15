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
        $todayProcedures = Procedure::whereDate('created_at', today());
        $thisWeekProcedures = Procedure::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);

        return [
            Stat::make('Ready to Process', Procedure::where('status', 'sign')->count())
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning')
                ->description('Awaiting validation')
                ->chart([10, 15, 12, 18, 14, 20, Procedure::where('status', 'sign')->count()]),

            Stat::make('Validated Today', Procedure::where('status', 'valid')->whereDate('updated_at', today())->count())
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->description('Processed today'),

            Stat::make('This Week', $thisWeekProcedures->count())
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->description('Procedures this week')
                ->chart([50, 60, 55, 70, 65, 80, $thisWeekProcedures->count()]),

            Stat::make('Total Validated', Procedure::where('status', 'valid')->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('All validated claims'),

            Stat::make('Returned', Procedure::where('status', Procedure::STATUS_RETURN)->count())
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->description('Sent back for review'),

            Stat::make('Invalid', Procedure::where('status', 'invalid')->count())
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->description('Marked as invalid'),

            Stat::make('Pending Signature', Procedure::where('status', 'pending')->count())
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Awaiting dentist'),
        ];
    }
}
