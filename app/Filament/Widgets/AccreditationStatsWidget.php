<?php

namespace App\Filament\Widgets;

use App\Models\Clinic;
use App\Models\Dentist;
use App\Filament\Resources\ClinicsResource;
use App\Filament\Resources\DentistResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccreditationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $clinicUrl = ClinicsResource::getUrl('index');

        $stats = [
            Stat::make('Active Clinics', Clinic::where('accreditation_status', 'ACTIVE')->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Currently accredited')
                ->url($clinicUrl . '?' . http_build_query([
                    'tableFilters[accreditation_status][value]' => 'ACTIVE',
                ])),

            Stat::make('New Today', Clinic::whereDate('created_at', today())->count())
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->description('Registered today')
                ->url($clinicUrl),

            Stat::make('Inactive Clinics', Clinic::where('accreditation_status', 'INACTIVE')->count())
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->description('Currently inactive')
                ->url($clinicUrl . '?' . http_build_query([
                    'tableFilters[accreditation_status][value]' => 'INACTIVE',
                ])),

            Stat::make('Silent Status', Clinic::where('accreditation_status', 'SILENT')->count())
                ->icon('heroicon-o-eye-slash')
                ->color('warning')
                ->description('Under silent status')
                ->url($clinicUrl . '?' . http_build_query([
                    'tableFilters[accreditation_status][value]' => 'SILENT',
                ])),

            Stat::make('Total Dentists', Dentist::count())
                ->icon('heroicon-o-user-group')
                ->color('info')
                ->description('Registered practitioners')
                ->url(DentistResource::getUrl('index')),
        ];

        $pendingCount = Clinic::where('accreditation_status', 'PENDING')->count();
        if ($pendingCount > 0) {
            array_unshift($stats,
                Stat::make('Pending Approval', $pendingCount)
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->description('Awaiting accreditation')
                    ->url($clinicUrl . '?' . http_build_query([
                        'tableFilters[accreditation_status][value]' => 'PENDING',
                    ]))
            );
        }

        return $stats;
    }
}
