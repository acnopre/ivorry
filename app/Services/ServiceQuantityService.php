<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountService;
use App\Models\Member;
use App\Models\MemberService;

class ServiceQuantityService
{
    /**
     * Get the service record (MemberService for SHARED, AccountService for INDIVIDUAL).
     */
    public static function getServiceRecord(Member $member, int $serviceId): AccountService|MemberService|null
    {
        $account = $member->account;

        if (strtoupper($account->plan_type) === 'SHARED') {
            MemberService::initializeForFamily($member->card_number, $account->id);

            return MemberService::where('card_number', $member->card_number)
                ->where('account_id', $account->id)
                ->where('service_id', $serviceId)
                ->first();
        }

        return AccountService::where('account_id', $account->id)
            ->where('service_id', $serviceId)
            ->first();
    }

    /**
     * Check if service has available quantity.
     */
    public static function hasAvailableQuantity(Member $member, int $serviceId): bool
    {
        $record = static::getServiceRecord($member, $serviceId);
        if (!$record) return false;

        return $record->is_unlimited || $record->quantity > 0;
    }

    /**
     * Get quantity and unlimited status.
     */
    public static function getQuantityInfo(Member $member, int $serviceId): array
    {
        $record = static::getServiceRecord($member, $serviceId);

        return [
            'quantity' => $record->quantity ?? 0,
            'is_unlimited' => $record->is_unlimited ?? false,
        ];
    }

    /**
     * Deduct service quantity by amount.
     */
    public static function deduct(Member $member, int $serviceId, int $amount = 1): void
    {
        $record = static::getServiceRecord($member, $serviceId);
        if (!$record || $record->is_unlimited) return;

        $record->decrement('quantity', $amount);
    }

    /**
     * Return (increment) service quantity by amount.
     */
    public static function returnQuantity(Member $member, int $serviceId, int $amount = 1): void
    {
        $record = static::getServiceRecord($member, $serviceId);
        if (!$record || $record->is_unlimited) return;

        $record->increment('quantity', $amount);
    }
}
