<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\{Account, Member, Clinic, Procedure, GeneratedSoa, Role};
use App\Filament\Resources\{AccountResource, MemberResource, ClinicsResource, ProcedureResource, GeneratedSoaResource};

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        $accountUrl = AccountResource::getUrl('index');
        $procedureUrl = ProcedureResource::getUrl('index');

        return [
            // 1. Total Accounts
            Stat::make('Total Accounts', Account::count())
                ->description('Registered company accounts')
                ->icon('heroicon-o-briefcase')
                ->color('primary')
                ->url($accountUrl),

            // 2. Active Accounts
            Stat::make('Active Accounts', Account::where('account_status', 'active')->count())
                ->description('Currently active')
                ->descriptionIcon('heroicon-o-check-circle')
                ->icon('heroicon-o-building-office')
                ->color('success')
                ->url($accountUrl . '?' . http_build_query([
                    'tableFilters[account_status][values][0]' => 'active',
                ])),

            // 3. Expiring Accounts
            Stat::make('Expiring Accounts', Account::where('account_status', 'active')->whereBetween('expiration_date', [now(), now()->addDays(30)])->count())
                ->description('Expiring within 30 days')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->url($accountUrl . '?' . http_build_query([
                    'tableFilters[expiring_soon][isActive]' => true,
                ])),

            // 4. New Accounts Today
            Stat::make('New Accounts Today', Account::whereDate('created_at', today())->count())
                ->description('Created today')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->url($accountUrl . '?' . http_build_query([
                    'tableFilters[created_today][isActive]' => true,
                ])),

            // 5. Pending Endorsements
            Stat::make('Pending Endorsements', Account::where('endorsement_status', 'PENDING')->count())
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->url($accountUrl . '?' . http_build_query([
                    'tableFilters[endorsement_status][values][0]' => 'PENDING',
                ])),

            // 6. Pending Renewals
            Stat::make('Pending Renewals', Account::where('endorsement_type', 'RENEWAL')->where('endorsement_status', 'PENDING')->count())
                ->description('Awaiting renewal approval')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->url($accountUrl . '?' . http_build_query([
                    'tableFilters[endorsement_type][values][0]' => 'RENEWAL',
                    'tableFilters[endorsement_status][values][0]' => 'PENDING',
                ])),

            // 7. Pending Amendments
            Stat::make('Pending Amendments', Account::where('endorsement_type', 'AMENDMENT')->where('endorsement_status', 'PENDING')->count())
                ->description('Awaiting amendment approval')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->url($accountUrl . '?' . http_build_query([
                    'tableFilters[endorsement_type][values][0]' => 'AMENDMENT',
                    'tableFilters[endorsement_status][values][0]' => 'PENDING',
                ])),

            // 8. Fixed MBL
            Stat::make('Fixed MBL', Account::where('mbl_type', 'Fixed')->where('account_status', 'active')->count())
                ->description('Using fixed MBL balance')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->url($accountUrl . '?' . http_build_query([
                    'tableFilters[account_status][values][0]' => 'active',
                ])),

            // 9. Members
            Stat::make('Members', Member::count())
                ->description('Total enrolled members')
                ->icon('heroicon-o-users')
                ->color('success')
                ->url(MemberResource::getUrl('index')),

            // 10. Clinics
            Stat::make('Clinics', Clinic::count())
                ->description('Partner dental clinics')
                ->icon('heroicon-o-building-office')
                ->color('info')
                ->url(ClinicsResource::getUrl('index')),

            // 11. Pending Procedures
            Stat::make('Pending Procedures', Procedure::where('status', 'pending')->count())
                ->description('Awaiting signature')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->url($procedureUrl . '?' . http_build_query([
                    'tableFilters[status][value]' => 'pending',
                ])),

            // 12. Signed Procedures
            Stat::make('Signed Procedures', Procedure::where('status', 'signed')->count())
                ->description('Completed today: ' . Procedure::where('status', 'signed')->whereDate('updated_at', today())->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->url($procedureUrl . '?' . http_build_query([
                    'tableFilters[status][value]' => 'signed',
                ])),

            // 13. Validated Procedures
            Stat::make('Validated Procedures', Procedure::where('status', 'valid')->count())
                ->description('Validated claims')
                ->icon('heroicon-o-check')
                ->color('success')
                ->url($procedureUrl . '?' . http_build_query([
                    'tableFilters[status][value]' => 'valid',
                ])),

            // 14. Pending ADC Requests
            Stat::make('Pending ADC Requests', GeneratedSoa::where('request_status', 'PENDING')->count())
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-clock')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->url(GeneratedSoaResource::getUrl('index') . '?' . http_build_query([
                    'tableFilters[request_status][value]' => 'PENDING',
                ])),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT]);
    }
}
