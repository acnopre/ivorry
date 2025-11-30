<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Service;

class AccountServiceManager
{
    /**
     * Load services for a given account and type.
     * Returns an array suitable for Filament form schema.
     */
    public static function loadServices(Account $account, string $type, bool $isAmendment): array
    {
        $services = $account
            ->services()
            ->where('services.type', $type)
            ->get();

        return $services->map(function ($service) use ($account, $type, $isAmendment) {

            $pivot = $service->pivot;

            return [
                'id' => $service->id,
                'name' => $service->name,
                'quantity' => $pivot->quantity ?? $service->default_quantity ?? null,
                'remarks' => $pivot->remarks ?? null,
                'is_unlimited' => $pivot->is_unlimited ?? ($type === 'basic' ? true : false),
                'disabled' => ! $isAmendment,
            ];
        })->toArray();
    }

    /**
     * Apply RENEWAL defaults: set quantity = default_quantity, is_unlimited = false
     */
    public static function applyRenewalDefaults(array &$services)
    {
        foreach ($services as &$service) {
            $service['quantity'] = $service['quantity'] ?? $service['default_quantity'] ?? 1;
            $service['is_unlimited'] = false;
        }
    }

    /**
     * Sync form data back to the pivot table
     */
    public static function syncPivotData(Account $account, array $servicesData, string $type)
    {
        foreach ($servicesData as $serviceId => $data) {
            $account->services()->updateExistingPivot($serviceId, [
                'quantity' => $data['quantity'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'is_unlimited' => $data['is_unlimited'] ?? false,
            ]);
        }
    }
}
