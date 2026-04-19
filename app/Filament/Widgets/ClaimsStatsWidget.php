<?php

namespace App\Filament\Widgets;

use App\Models\Dentist;
use App\Models\Procedure;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ClaimsStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = Cache::remember('claims_stats', 30, function () {
            return [
                'signed'         => Procedure::where('status', 'signed')->count(),
                'valid_today'    => Procedure::where('status', 'valid')->whereDate('updated_at', today())->count(),
                'this_week'      => Procedure::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'total_valid'    => Procedure::where('status', 'valid')->count(),
                'returned'       => Procedure::where('status', Procedure::STATUS_RETURN)->count(),
                'invalid'        => Procedure::where('status', 'invalid')->count(),
                'pending'        => Procedure::where('status', 'pending')->count(),
            ];
        });

        return [
            Stat::make('Ready to Process', $stats['signed'])
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning')
                ->description('Awaiting validation'),

            Stat::make('Validated Today', $stats['valid_today'])
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->description('Processed today'),

            Stat::make('This Week', $stats['this_week'])
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->description('Procedures this week'),

            Stat::make('Total Validated', $stats['total_valid'])
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('All validated claims'),

            Stat::make('Returned', $stats['returned'])
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->description('Sent back for review'),

            Stat::make('Invalid', $stats['invalid'])
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->description('Marked as invalid'),

            Stat::make('Pending Signature', $stats['pending'])
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Awaiting dentist'),
        ];
    }
}
