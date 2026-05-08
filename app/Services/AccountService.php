<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Hip;
use App\Models\Service;

class AccountService
{
    /**
     * Check if a company + HIP combination already exists.
     * Optionally exclude a specific account ID (for edit scenarios).
     */
    public static function isDuplicateCompanyHip(string $companyName, int $hipId, ?int $excludeAccountId = null): bool
    {
        return Account::where('company_name', $companyName)
            ->where('hip_id', $hipId)
            ->when($excludeAccountId, fn($q) => $q->where('id', '!=', $excludeAccountId))
            ->exists();
    }

    /**
     * Return a human-readable duplicate error message.
     */
    public static function duplicateMessage(string $companyName, int $hipId): string
    {
        $hipName = Hip::find($hipId)?->name ?? $hipId;
        return "Company '{$companyName}' already has an account under HIP '{$hipName}'. Please try again.";
    }

    /**
     * Sync services to an account from a flat pivot array.
     * $pivotData: [ service_id => ['quantity' => x, 'is_unlimited' => bool, 'remarks' => ?string] ]
     */
    public static function syncServices(Account $account, array $pivotData): void
    {
        $filtered = collect($pivotData)
            ->filter(
                fn($d, $serviceId) => (int) $serviceId > 0 && (
                    (isset($d['quantity']) && (int) $d['quantity'] > 0)
                    || ($d['is_unlimited'] ?? false)
                    || !empty($d['remarks'])
                )
            )
            ->mapWithKeys(fn($d, $serviceId) => [
                $serviceId => [
                    'quantity'         => $d['quantity'] ?? null,
                    'default_quantity' => $d['quantity'] ?? null,
                    'is_unlimited'     => filter_var($d['is_unlimited'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'remarks'          => $d['remarks'] ?? null,
                ],
            ])
            ->toArray();

        $account->services()->sync($filtered);
    }

    /**
     * Sync services from a typed form array (basic/enhancement/special keys).
     */
    public static function syncServicesFromForm(Account $account, array $servicesByType): void
    {
        // Use + operator to preserve numeric service_id keys (array_merge reindexes them to 0,1,2...)
        $merged = ($servicesByType['basic'] ?? [])
            + ($servicesByType['enhancement'] ?? [])
            + ($servicesByType['special'] ?? []);

        self::syncServices($account, $merged);
    }

    /**
     * Sync services from a flat import row using service slugs.
     */
    public static function syncServicesFromImportRow(Account $account, $services, array $row): void
    {
        $pivotData = [];

        foreach ($services as $service) {
            $slug = $service->slug;
            if (!isset($row[$slug])) continue;

            $value       = $row[$slug];
            $isUnlimited = $service->type === 'basic' || strtolower((string) $value) === 'unlimited';
            $quantity    = $isUnlimited ? null : (is_numeric($value) ? (int) $value : 0);

            $pivotData[$service->id] = [
                'quantity'         => $quantity,
                'default_quantity' => $quantity,
                'is_unlimited'     => $isUnlimited,
            ];
        }

        if (!empty($pivotData)) {
            $account->services()->sync($pivotData);
        }
    }

    /**
     * Determine account_status based on dates and migration mode.
     */
    public static function resolveStatus(bool $migrationMode, ?string $expirationDate = null): string
    {
        if (!$migrationMode) return 'inactive';
        if ($expirationDate && $expirationDate < now()->format('Y-m-d')) return 'expired';
        return 'active';
    }
}
