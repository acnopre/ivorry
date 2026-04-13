<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;

class DeactivateMembers extends Command
{
    protected $signature = 'members:deactivate';
    protected $description = 'Deactivate members whose inactive_date has passed';

    public function handle(): int
    {
        $count = Member::where('status', 'ACTIVE')
            ->whereNotNull('inactive_date')
            ->whereDate('inactive_date', '<=', now())
            ->update(['status' => 'INACTIVE']);

        $this->info("Deactivated {$count} member(s).");

        return self::SUCCESS;
    }
}
