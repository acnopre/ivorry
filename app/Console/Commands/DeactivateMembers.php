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
            ->update([
                'status'        => 'INACTIVE',
            ]);

        // 2. Deactivate members whose expiration_date has passed (MEMBER coverage type)
        // Uses '<' so members have full access through their expiration date
        // e.g. expiration Apr 30 - deactivated on May 1
        $byExpiration = Member::where('status', 'ACTIVE')
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<', now())
            ->whereHas('account', fn($q) => $q->where('coverage_period_type', 'MEMBER'))
            ->update([
                'status'        => 'INACTIVE',
                'inactive_date' => now()->toDateString(),
            ]);

        $this->info("Deactivated {$byInactiveDate} member(s) by inactive_date.");
        $this->info("Deactivated {$byExpiration} member(s) by expiration_date.");

        return self::SUCCESS;
    }
}
