<?php

namespace App\Filament\Widgets;

use App\Models\{Account, Member, Procedure, Role};
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class CSRStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = Cache::remember('csr_stats', 30, function () {
            return [
                'active_accounts'  => Account::where('account_status', 'active')->count(),
                'active_members'   => Member::where('status', 'active')->count(),
                'today_procedures' => Procedure::whereDate('created_at', today())->count(),
                'pending'          => Procedure::where('status', 'pending')->count(),
                'signed_today'     => Procedure::where('status', 'signed')->whereDate('updated_at', today())->count(),
                'signed'           => Procedure::where('status', 'signed')->count(),
            ];
        });

        return [
            Stat::make('Active Accounts', $stats['active_accounts'])
                ->icon('heroicon-o-briefcase')
                ->color('success')
                ->description('Currently active'),

            Stat::make('Active Members', $stats['active_members'])
                ->icon('heroicon-o-users')
                ->color('success')
                ->description('Currently active members'),

            Stat::make("Today's Procedures", $stats['today_procedures'])
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->description('Created today'),

            Stat::make('Pending Signature', $stats['pending'])
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Awaiting dentist signature'),

            Stat::make('Signed Today', $stats['signed_today'])
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Completed today'),

            Stat::make('Ready for Processing', $stats['signed'])
                ->icon('heroicon-o-document-check')
                ->color('info')
                ->description('Awaiting validation'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole(Role::CSR);
    }
}
