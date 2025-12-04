<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Clinic;
use App\Models\Dentist;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AccreditationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $clinicQuery = Clinic::query();
        $dentistQuery = Dentist::query();

        return [
            // CLINIC STATUS COUNTS
            Stat::make('Active Clinics', $clinicQuery->clone()->where('accreditation_status', 'ACTIVE')->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Clinics with active accreditation'),

            Stat::make('Inactive Clinics', $clinicQuery->clone()->where('accreditation_status', 'INACTIVE')->count())
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->description('Clinics currently inactive'),

            Stat::make('Silent Clinics', $clinicQuery->clone()->where('accreditation_status', 'SILENT')->count())
                ->icon('heroicon-o-eye-slash')
                ->color('warning')
                ->description('Clinics under silent status'),

            Stat::make('Specific Account Clinics', $clinicQuery->clone()->where('accreditation_status', 'SPECIFIC ACCOUNT')->count())
                ->icon('heroicon-o-briefcase')
                ->color('info')
                ->description('Clinics with specific account restrictions'),

            // PTR COUNTS
            Stat::make('Clinics with PTR', $clinicQuery->clone()->whereNotNull('ptr_no')->count())
                ->icon('heroicon-o-document-check')
                ->color('success')
                ->description('Clinics that have PTR information'),

            Stat::make('Clinics without PTR', $clinicQuery->clone()->whereNull('ptr_no')->count())
                ->icon('heroicon-o-document')
                ->color('danger')
                ->description('Clinics missing PTR information'),

            // TIN COUNTS
            Stat::make('Clinics with TIN', $clinicQuery->clone()->whereNotNull('tax_identification_no')->count())
                ->icon('heroicon-o-identification')
                ->color('success')
                ->description('Clinics with valid TIN'),

            // BRANCH vs MAIN
            Stat::make('Branch Clinics', $clinicQuery->clone()->where('is_branch', true)->count())
                ->icon('heroicon-o-building-office')
                ->color('info')
                ->description('Clinics marked as branch'),

            Stat::make('Main Clinics', $clinicQuery->clone()->where('is_branch', false)->count())
                ->icon('heroicon-o-home-modern')
                ->color('primary')
                ->description('Main clinics'),

            // DENTISTS
            Stat::make('Total Dentists', $dentistQuery->count())
                ->icon('heroicon-o-user-group')
                ->color('info')
                ->description('All registered dentists'),

            Stat::make('Dentist Owners', $dentistQuery->clone()->where('is_owner', 1)->count())
                ->icon('heroicon-o-user-circle')
                ->color('success')
                ->description('Dentists who own their clinic'),

            Stat::make('Associate Dentists', $dentistQuery->clone()->where('is_owner', 0)->count())
                ->icon('heroicon-o-user')
                ->color('secondary')
                ->description('Dentists who are not owners'),
        ];
    }
}
