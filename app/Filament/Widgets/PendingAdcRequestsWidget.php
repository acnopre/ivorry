<?php

namespace App\Filament\Widgets;

use App\Models\GeneratedSoa;
use App\Models\Role;
use App\Filament\Resources\GeneratedSoaResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingAdcRequestsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $pending  = GeneratedSoa::where('request_status', 'PENDING')->count();
        $approved = GeneratedSoa::where('request_status', 'APPROVED')->count();
        $total    = GeneratedSoa::whereNotNull('request_status')->count();

        return [
            Stat::make('Pending ADC Requests', $pending)
                ->description('Awaiting your approval')
                ->descriptionIcon('heroicon-o-clock')
                ->icon('heroicon-o-document-text')
                ->color($pending > 0 ? 'warning' : 'success')
                ->url(GeneratedSoaResource::getUrl('index') . '?' . http_build_query([
                    'tableFilters[request_status][value]' => 'PENDING',
                ])),

            // Stat::make('Approved ADC Requests', $approved)
            //     ->description('Original approved')
            //     ->descriptionIcon('heroicon-o-check-circle')
            //     ->icon('heroicon-o-check-badge')
            //     ->color('success')
            //     ->url(GeneratedSoaResource::getUrl('index') . '?' . http_build_query([
            //         'tableFilters[request_status][value]' => 'APPROVED',
            //     ])),

            // Stat::make('Total ADC Requests', $total)
            //     ->description('All original requests')
            //     ->icon('heroicon-o-document-duplicate')
            //     ->color('info')
            //     ->url(GeneratedSoaResource::getUrl('index')),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole([
                Role::SUPER_ADMIN,
                Role::UPPER_MANAGEMENT,
                Role::MIDDLE_MANAGEMENT,
            ]);
    }
}
