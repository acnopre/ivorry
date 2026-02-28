<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Procedure;

class MblBalanceService
{
    public static function handleMblTypeChange(int $accountId, string $oldType, string $newType, ?float $newAmount, string $effectiveDate): void
    {
        // Procedural to Fixed: Calculate and set balance
        if ($oldType === 'Procedural' && $newType === 'Fixed' && $newAmount) {
            $members = Member::where('account_id', $accountId)->get();
            
            foreach ($members as $member) {
                $balance = $newAmount;

                $procedures = Procedure::where('member_id', $member->id)
                    ->where('availment_date', '>=', $effectiveDate)
                    ->get();

                foreach ($procedures as $procedure) {
                    $balance -= $procedure->applied_fee ?? 0;
                }

                $member->update(['mbl_balance' => $balance]);
            }
        }

        // Fixed to Procedural: Remove balance
        if ($oldType === 'Fixed' && $newType === 'Procedural') {
            Member::where('account_id', $accountId)->update(['mbl_balance' => null]);
        }
    }
}
