<?php

namespace App\Console\Commands;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Models\AccountRenewal;
use App\Models\AccountService;
use App\Models\AccountRenewalService;
use App\Models\AccountServiceHistory;
use App\Models\Member;
use App\Models\MemberService;
use App\Models\Role;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class ActivateAccounts extends Command
{
    protected $signature = 'accounts:activate';
    protected $description = 'Activate accounts/renewals and their members when effective_date is reached';

    public function handle(): int
    {
        $this->processNewAccounts();
        $this->processRenewals();

        return self::SUCCESS;
    }

    protected function processNewAccounts(): void
    {
        $accounts = Account::where('account_status', 'inactive')
            ->whereNotNull('effective_date')
            ->whereDate('effective_date', '<=', now())
            ->whereDate('expiration_date', '>=', now())
            ->where('endorsement_status', 'APPROVED')
            ->whereDoesntHave('renewals', fn($q) => $q->where('status', 'APPROVED_PENDING_EFFECTIVE'))
            ->get();

        if ($accounts->isEmpty()) return;

        $accountIds = $accounts->pluck('id');

        Account::whereIn('id', $accountIds)->update(['account_status' => 'active']);

        $activatedMembers = Member::whereIn('account_id', $accountIds)
            ->where('status', 'INACTIVE')
            ->whereNull('inactive_date')
            ->update(['status' => 'ACTIVE']);

        $notifyUsers = User::role([Role::UPPER_MANAGEMENT, Role::MIDDLE_MANAGEMENT, Role::ACCOUNT_MANAGER])->get();

        foreach ($accounts as $account) {
            $memberCount = Member::where('account_id', $account->id)->where('status', 'ACTIVE')->count();
            Notification::make()
                ->title('Account Now Active')
                ->body("{$account->company_name} ({$account->policy_code}) is now active. {$memberCount} member(s) activated.")
                ->success()
                ->actions([NotificationAction::make('view')->label('View Account')->url(AccountResource::getUrl('view', ['record' => $account]))])
                ->sendToDatabase($notifyUsers);
        }

        $this->info("New accounts: activated {$accounts->count()} account(s) and {$activatedMembers} member(s).");
    }

    protected function processRenewals(): void
    {
        $renewals = AccountRenewal::where('status', 'APPROVED_PENDING_EFFECTIVE')
            ->whereDate('effective_date', '<=', now())
            ->with('account')
            ->get();

        if ($renewals->isEmpty()) return;

        $notifyUsers = User::role([Role::UPPER_MANAGEMENT, Role::MIDDLE_MANAGEMENT, Role::ACCOUNT_MANAGER])->get();

        foreach ($renewals as $renewal) {
            $account = $renewal->account;
            if (! $account) continue;

            // 1. Archive current services
            $currentServices = AccountService::where('account_id', $account->id)->get();
            foreach ($currentServices as $service) {
                AccountServiceHistory::create([
                    'account_id'      => $account->id,
                    'service_id'      => $service->service_id,
                    'quantity'        => $service->quantity,
                    'remarks'         => 'Archived on renewal effective',
                    'action'          => 'renewal',
                    'effective_date'  => $account->effective_date,
                    'expiration_date' => $account->expiration_date,
                ]);
            }
            AccountService::where('account_id', $account->id)->delete();

            // 2. Apply renewal services
            $renewalServices = AccountRenewalService::where('renewal_id', $renewal->id)->get();
            foreach ($renewalServices as $service) {
                AccountService::create([
                    'account_id'       => $account->id,
                    'renewal_id'       => $renewal->id,
                    'service_id'       => $service->service_id,
                    'quantity'         => $service->quantity,
                    'default_quantity' => $service->default_quantity ?? $service->quantity,
                    'is_unlimited'     => $service->is_unlimited,
                    'remarks'          => $service->remarks,
                ]);
            }

            // 3. Deactivate old members (no renewal_id or different renewal_id)
            $deactivated = Member::where('account_id', $account->id)
                ->where('status', 'ACTIVE')
                ->where(fn($q) => $q->whereNull('renewal_id')->orWhere('renewal_id', '!=', $renewal->id))
                ->update(['status' => 'INACTIVE', 'inactive_date' => now()->format('Y-m-d')]);

            // 4. Activate new members tagged with this renewal
            $activated = Member::where('account_id', $account->id)
                ->where('renewal_id', $renewal->id)
                ->where('status', 'INACTIVE')
                ->update([
                    'status'       => 'ACTIVE',
                    'inactive_date' => null,
                    'mbl_balance'  => $account->mbl_type === 'Fixed' ? $account->mbl_amount : null,
                ]);

            // 5. Update account dates
            $account->update([
                'effective_date'   => $renewal->effective_date,
                'expiration_date'  => $renewal->expiration_date,
                'endorsement_type' => 'RENEWED',
                'endorsement_status' => 'APPROVED',
                'account_status'   => 'active',
            ]);

            // 6. Reset MemberService for SHARED
            if (strtoupper($account->plan_type) === 'SHARED') {
                MemberService::where('account_id', $account->id)->delete();
                $account->members()->where('status', 'ACTIVE')->pluck('card_number')
                    ->unique()->filter()
                    ->each(fn($cardNumber) => MemberService::initializeForFamily($cardNumber, $account->id));
            }

            // 7. Mark renewal as APPROVED
            $renewal->update(['status' => 'APPROVED']);

            // 8. Notify
            Notification::make()
                ->title('Account Renewal Now Active')
                ->body("{$account->company_name} ({$account->policy_code}) renewal is now active. {$activated} member(s) activated, {$deactivated} deactivated.")
                ->success()
                ->actions([NotificationAction::make('view')->label('View Account')->url(AccountResource::getUrl('view', ['record' => $account]))])
                ->sendToDatabase($notifyUsers);

            $this->info("Renewal processed for {$account->company_name}: {$activated} activated, {$deactivated} deactivated.");
        }
    }
}
