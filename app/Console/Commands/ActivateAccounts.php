<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Member;
use Illuminate\Console\Command;

class ActivateAccounts extends Command
{
    protected $signature = 'accounts:activate';
    protected $description = 'Activate accounts and their members when effective_date is reached';

    public function handle(): int
    {
        $accountIds = Account::where('account_status', 'inactive')
            ->whereNotNull('effective_date')
            ->whereDate('effective_date', '<=', now())
            ->whereDate('expiration_date', '>=', now())
            ->where('endorsement_status', 'APPROVED')
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            $this->info('No accounts to activate.');
            return self::SUCCESS;
        }

        $activatedAccounts = Account::whereIn('id', $accountIds)
            ->update(['account_status' => 'active']);

        $activatedMembers = Member::whereIn('account_id', $accountIds)
            ->where('status', 'INACTIVE')
            ->whereNull('inactive_date')
            ->update(['status' => 'ACTIVE']);

        $this->info("Activated {$activatedAccounts} account(s) and {$activatedMembers} member(s).");

        return self::SUCCESS;
    }
}
