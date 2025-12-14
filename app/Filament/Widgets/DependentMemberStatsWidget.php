<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DependentMemberStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $accountId = Auth::user()->member->account_id;

        // Count dependents
        $dependentsCount = Member::query()
            ->where('member_type', 'DEPENDENT')
            ->where('account_id', $accountId)
            ->count();

        // Get services with total quantity and unlimited flag for this account
        $services = Service::query()
            ->with(['accountServices' => function ($query) use ($accountId) {
                $query->where('account_id', $accountId);
            }])
            ->get();

        // Only keep services that have quantity > 0 or are unlimited
        $services = $services->filter(
            fn($service) =>
            $service->accountServices->sum('quantity') > 0 ||
                $service->accountServices->contains(fn($as) => $as->is_unlimited)
        );

        $serviceStats = $services->map(function ($service) {
            $accountService = $service->accountServices->first();
            $displayValue = $accountService->is_unlimited ? 'Unlimited' : $service->accountServices->sum('quantity');

            return Stat::make($service->name, $displayValue)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary')
                ->description("Total {$service->unit_type}(s) services");
        })->toArray();

        return array_merge([
            Stat::make('Dependents', $dependentsCount)
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->description('Total dependent members'),
        ], $serviceStats);
    }
}
