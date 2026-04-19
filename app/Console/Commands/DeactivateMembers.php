<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;

class DeactivateMembers extends Command
{
    protected $signature = 'members:deactivate';
    protected $description = 'Deactivate members whose inactive_date or expiration_date has passed';

    public function handle(): int
    {
        // 1. Deactivate members whose inactive_date has passed
        $byInactiveDate = Member::where('status', 'ACTIVE')
            ->whereNotNull('inactive_date')
            ->whereDate('inactive_date', '<=', now())
            ->update(['status' => 'INACTIVE']);

        // 2. Deactivate members whose expiration_date has passed (MEMBER coverage type)
        //    Only applies when account coverage_period_type = MEMBER (member has own expiration_date)
        $byExpiration = Member::where('status', 'ACTIVE')
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<', now()->startOfDay())
            ->whereHas('account', fn($q) => $q->where('coverage_period_type', 'MEMBER'))
            ->update(['status' => 'INACTIVE']);

        $this->info("Deactivated {$byInactiveDate} member(s) by inactive_date.");
        $this->info("Deactivated {$byExpiration} member(s) by expiration_date.");

        return self::SUCCESS;
    }
}
