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
        $todayClinics = Clinic::whereDate('created_at', today());
        $pendingClinics = $clinicQuery->clone()->where('accreditation_status', 'PENDING');

        $stats = [
            Stat::make('Active Clinics', $clinicQuery->clone()->where('accreditation_status', 'ACTIVE')->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Currently accredited')
                ->chart([20, 25, 23, 28, 26, 30, $clinicQuery->clone()->where('accreditation_status', 'ACTIVE')->count()]),

            Stat::make('New Today', $todayClinics->count())
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->description('Registered today'),

            Stat::make('Inactive Clinics', $clinicQuery->clone()->where('accreditation_status', 'INACTIVE')->count())
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->description('Currently inactive'),

            Stat::make('Silent Status', $clinicQuery->clone()->where('accreditation_status', 'SILENT')->count())
                ->icon('heroicon-o-eye-slash')
                ->color('warning')
                ->description('Under silent status'),


            Stat::make('Total Dentists', Dentist::count())
                ->icon('heroicon-o-user-group')
                ->color('info')
                ->description('Registered practitioners'),
        ];

        if ($pendingClinics->count() > 0) {
            array_unshift(
                $stats,
                Stat::make('Pending Approval', $pendingClinics->count())
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->description('Awaiting accreditation')
            );
        }

        return $stats;
    }
}
