<?php

namespace App\Console\Commands;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class ActivateAccounts extends Command
{
    protected $signature = 'accounts:activate';
    protected $description = 'Activate accounts and their members when effective_date is reached';

    public function handle(): int
    {
        $accounts = Account::where('account_status', 'inactive')
            ->whereNotNull('effective_date')
            ->whereDate('effective_date', '<=', now())
            ->whereDate('expiration_date', '>=', now())
            ->where('endorsement_status', 'APPROVED')
            ->get();

        if ($accounts->isEmpty()) {
            $this->info('No accounts to activate.');
            return self::SUCCESS;
        }

        $accountIds = $accounts->pluck('id');

        Account::whereIn('id', $accountIds)->update(['account_status' => 'active']);

        $activatedMembers = Member::whereIn('account_id', $accountIds)
            ->where('status', 'INACTIVE')
            ->whereNull('inactive_date')
            ->update(['status' => 'ACTIVE']);

        // Notify relevant users per account
        $notifyRoles = [Role::UPPER_MANAGEMENT, Role::MIDDLE_MANAGEMENT, Role::ACCOUNT_MANAGER];
        $notifyUsers = User::role($notifyRoles)->get();

        foreach ($accounts as $account) {
            $memberCount = Member::where('account_id', $account->id)->count();
            $url = AccountResource::getUrl('view', ['record' => $account]);

            Notification::make()
                ->title('Account Now Active')
                ->body("Account {$account->company_name} ({$account->policy_code}) is now active. {$memberCount} member(s) have been activated.")
                ->success()
                ->actions([
                    NotificationAction::make('view')
                        ->label('View Account')
                        ->url($url),
                ])
                ->sendToDatabase($notifyUsers);
        }

        $this->info("Activated {$accounts->count()} account(s) and {$activatedMembers} member(s).");

        return self::SUCCESS;
    }
}
