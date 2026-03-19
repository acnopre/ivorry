<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\{Account, Member, Clinic, Dentist, Procedure, Role};
use App\Filament\Resources\{AccountResource, MemberResource, ClinicsResource, ProcedureResource};

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Accounts', Account::count())
                ->description('Registered company accounts')
                ->icon('heroicon-o-briefcase')
                ->color('primary')
                ->chart([7, 12, 8, 15, 10, 18, 20])
                ->url(AccountResource::getUrl('index')),

            Stat::make('Active Accounts', Account::where('account_status', 'active')->count())
                ->description('Currently active')
                ->descriptionIcon('heroicon-o-check-circle')
                ->icon('heroicon-o-building-office')
                ->color('success')
                ->url(AccountResource::getUrl('index', ['tableFilter' => 'account_status:active'])),

            Stat::make('Pending Endorsements', Account::where('endorsement_status', 'PENDING')->count())
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->url(AccountResource::getUrl('index', ['tableFilter' => 'endorsement_status:PENDING'])),

            Stat::make('Fixed MBL Accounts', Account::where('mbl_type', 'Fixed')->where('account_status', 'active')->count())
                ->description('Using fixed MBL balance')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->url(AccountResource::getUrl('index', ['tableFilter' => 'account_status:active'])),

            Stat::make('Members', Member::count())
                ->description('Total enrolled members')
                ->icon('heroicon-o-users')
                ->color('success')
                ->chart([50, 60, 55, 70, 65, 80, 85])
                ->url(MemberResource::getUrl('index')),

            Stat::make('Clinics', Clinic::count())
                ->description('Partner dental clinics')
                ->icon('heroicon-o-building-office')
                ->color('info')
                ->url(ClinicsResource::getUrl('index')),

            Stat::make('Pending Procedures', Procedure::where('status', 'pending')->count())
                ->description('Awaiting signature')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->url(ProcedureResource::getUrl('index', ['tableFilter' => 'status:pending'])),

            Stat::make('Sign Procedures', Procedure::where('status', 'signed')->count())
                ->description('Completed today: ' . Procedure::where('status', 'signed')->whereDate('updated_at', today())->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->url(ProcedureResource::getUrl('index', ['tableFilter' => 'status:signed'])),

            Stat::make('Valid Procedures', Procedure::where('status', 'valid')->count())
                ->description('Validated claims')
                ->icon('heroicon-o-check')
                ->color('success')
                ->url(ProcedureResource::getUrl('index', ['tableFilter' => 'status:valid'])),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT]);
    }
}
