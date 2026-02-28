<?php

namespace App\Services;

use App\Models\AccountRenewal;
use App\Models\AccountAmendment;
use Illuminate\Support\Collection;

class AccountEndorsementService
{
    public static function attachServicesToRenewal(AccountRenewal $renewal, Collection|array $accountServices): void
    {
        foreach ($accountServices as $accountService) {
            $defaultQty = ($accountService->default_quantity ?? 0) ?: ($accountService->quantity ?? null);

            $renewal->services()->create([
                'service_id' => $accountService->service_id,
                'quantity' => $defaultQty,
                'default_quantity' => $defaultQty,
                'is_unlimited' => $accountService->is_unlimited,
            ]);
        }
    }

    public static function attachServicesToRenewalFromForm(AccountRenewal $renewal, array $servicesByType): void
    {
        foreach (['basic', 'enhancement', 'special'] as $type) {
            if (empty($servicesByType[$type])) continue;

            foreach ($servicesByType[$type] as $serviceId => $serviceData) {
                $defaultQty = ($serviceData['default_quantity'] ?? 0) ?: ($serviceData['quantity'] ?? null);

                $renewal->services()->create([
                    'service_id' => $serviceId,
                    'quantity' => $defaultQty,
                    'default_quantity' => $defaultQty,
                    'is_unlimited' => $serviceData['is_unlimited'] ?? false,
                    'remarks' => $serviceData['remarks'] ?? null,
                ]);
            }
        }
    }

    public static function attachServicesToAmendmentFromForm(AccountAmendment $amendment, array $servicesByType): void
    {
        foreach (['basic', 'enhancement', 'special'] as $type) {
            if (empty($servicesByType[$type])) continue;

            foreach ($servicesByType[$type] as $serviceId => $serviceData) {
                $newQty = $serviceData['quantity'] ?? null;

                $amendment->services()->create([
                    'service_id' => $serviceId,
                    'quantity' => $newQty,
                    'default_quantity' => $newQty,
                    'is_unlimited' => $serviceData['is_unlimited'] ?? false,
                    'remarks' => $serviceData['remarks'] ?? null,
                ]);
            }
        }
    }

    public static function deletePendingRenewals(int $accountId): void
    {
        $pendingRenewals = AccountRenewal::where('account_id', $accountId)
            ->where('status', 'PENDING')
            ->get();

        foreach ($pendingRenewals as $pendingRenewal) {
            $pendingRenewal->services()->delete();
            $pendingRenewal->delete();
        }
    }

    public static function deletePendingAmendments(int $accountId): void
    {
        $existingAmendments = AccountAmendment::where('account_id', $accountId)
            ->where('endorsement_status', 'PENDING')
            ->get();

        foreach ($existingAmendments as $amendment) {
            $amendment->services()->delete();
            $amendment->delete();
        }
    }
}
