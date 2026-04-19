<?php

namespace App\Filament\Widgets;

use App\Models\Procedure;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DentistStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $clinicId = Auth::user()->clinic->id;

        $stats = Cache::remember("dentist_stats_{$clinicId}", 30, function () use ($clinicId) {
            return [
                'today'       => Procedure::where('clinic_id', $clinicId)->whereDate('created_at', today())->count(),
                'this_month'  => Procedure::where('clinic_id', $clinicId)->whereMonth('created_at', now()->month)->count(),
                'pending'     => Procedure::where('status', 'pending')->where('clinic_id', $clinicId)->count(),
                'signed_today'=> Procedure::where('status', 'signed')->where('clinic_id', $clinicId)->whereDate('updated_at', today())->count(),
                'total_signed'=> Procedure::where('status', 'signed')->where('clinic_id', $clinicId)->count(),
                'returned'    => Procedure::where('status', 'returned')->where('clinic_id', $clinicId)->count(),
            ];
        });

        return [
            Stat::make("Today's Procedures", $stats['today'])
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->description('Created today'),

            Stat::make('This Month', $stats['this_month'])
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->description('Procedures this month'),

            Stat::make('Pending Signature', $stats['pending'])
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Awaiting your signature')
                ->url(route('filament.admin.resources.procedures.index', ['tableFilters[status][value]' => 'pending'])),

            Stat::make('Signed Today', $stats['signed_today'])
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Completed today'),

            Stat::make('Total Signed', $stats['total_signed'])
                ->icon('heroicon-o-document-check')
                ->color('success')
                ->description('All signed procedures'),

            Stat::make('Returned', $stats['returned'])
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->description('Needs attention'),
        ];
    }
}
