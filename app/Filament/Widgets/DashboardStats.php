<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\{Account, Member, Clinic, Procedure, GeneratedSoa, Role};
use App\Filament\Resources\{AccountResource, MemberResource, ClinicsResource, ProcedureResource, GeneratedSoaResource};
use Illuminate\Support\Facades\Cache;

class DashboardStats extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $accountUrl  = AccountResource::getUrl('index');
        $procedureUrl = ProcedureResource::getUrl('index');

        $stats = Cache::remember('dashboard_stats', 60, function () {
            return [
                'total_accounts'      => Account::count(),
                'active_accounts'     => Account::where('account_status', 'active')->count(),
                'expiring_accounts'   => Account::where('account_status', 'active')->whereBetween('expiration_date', [now(), now()->addDays(30)])->count(),
                'new_today'           => Account::whereDate('created_at', today())->count(),
                'pending_endorsements'=> Account::where('endorsement_status', 'PENDING')->count(),
                'pending_renewals'    => Account::where('endorsement_type', 'RENEWAL')->where('endorsement_status', 'PENDING')->count(),
                'pending_amendments'  => Account::where('endorsement_type', 'AMENDMENT')->where('endorsement_status', 'PENDING')->count(),
                'fixed_mbl'           => Account::where('mbl_type', 'Fixed')->where('account_status', 'active')->count(),
                'members'             => Member::count(),
                'clinics'             => Clinic::count(),
                'pending_procedures'  => Procedure::where('status', 'pending')->count(),
                'signed_procedures'   => Procedure::where('status', 'signed')->count(),
                'signed_today'        => Procedure::where('status', 'signed')->whereDate('updated_at', today())->count(),
                'valid_procedures'    => Procedure::where('status', 'valid')->count(),
                'pending_adc'         => GeneratedSoa::where('request_status', 'PENDING')->count(),
            ];
        });

        return [
            Stat::make('Total Accounts', $stats['total_accounts'])
                ->description('Registered company accounts')
                ->icon('heroicon-o-briefcase')
                ->color('primary')
                ->url($accountUrl),

            Stat::make('Active Accounts', $stats['active_accounts'])
                ->description('Currently active')
                ->descriptionIcon('heroicon-o-check-circle')
                ->icon('heroicon-o-building-office')
                ->color('success')
                ->url($accountUrl . '?' . http_build_query(['tableFilters[account_status][values][0]' => 'active'])),

            Stat::make('Expiring Accounts', $stats['expiring_accounts'])
                ->description('Expiring within 30 days')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->url($accountUrl . '?' . http_build_query(['tableFilters[expiring_soon][isActive]' => true])),

            Stat::make('New Accounts Today', $stats['new_today'])
                ->description('Created today')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->url($accountUrl . '?' . http_build_query(['tableFilters[created_today][isActive]' => true])),

            Stat::make('Pending Endorsements', $stats['pending_endorsements'])
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->url($accountUrl . '?' . http_build_query(['tableFilters[endorsement_status][values][0]' => 'PENDING'])),

            Stat::make('Pending Renewals', $stats['pending_renewals'])
                ->description('Awaiting renewal approval')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->url($accountUrl . '?' . http_build_query(['tableFilters[endorsement_type][values][0]' => 'RENEWAL', 'tableFilters[endorsement_status][values][0]' => 'PENDING'])),

            Stat::make('Pending Amendments', $stats['pending_amendments'])
                ->description('Awaiting amendment approval')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->url($accountUrl . '?' . http_build_query(['tableFilters[endorsement_type][values][0]' => 'AMENDMENT', 'tableFilters[endorsement_status][values][0]' => 'PENDING'])),

            Stat::make('Fixed MBL', $stats['fixed_mbl'])
                ->description('Using fixed MBL balance')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->url($accountUrl . '?' . http_build_query(['tableFilters[account_status][values][0]' => 'active'])),

            Stat::make('Members', $stats['members'])
                ->description('Total enrolled members')
                ->icon('heroicon-o-users')
                ->color('success')
                ->url(MemberResource::getUrl('index')),

            Stat::make('Clinics', $stats['clinics'])
                ->description('Partner dental clinics')
                ->icon('heroicon-o-building-office')
                ->color('info')
                ->url(ClinicsResource::getUrl('index')),

            Stat::make('Pending Procedures', $stats['pending_procedures'])
                ->description('Awaiting signature')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->url($procedureUrl . '?' . http_build_query(['tableFilters[status][value]' => 'pending'])),

            Stat::make('Signed Procedures', $stats['signed_procedures'])
                ->description('Completed today: ' . $stats['signed_today'])
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->url($procedureUrl . '?' . http_build_query(['tableFilters[status][value]' => 'signed'])),

            Stat::make('Validated Procedures', $stats['valid_procedures'])
                ->description('Validated claims')
                ->icon('heroicon-o-check')
                ->color('success')
                ->url($procedureUrl . '?' . http_build_query(['tableFilters[status][value]' => 'valid'])),

            Stat::make('Pending ADC Requests', $stats['pending_adc'])
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-clock')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->url(GeneratedSoaResource::getUrl('index') . '?' . http_build_query(['tableFilters[request_status][value]' => 'PENDING'])),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT, Role::MIDDLE_MANAGEMENT]);
    }
}
