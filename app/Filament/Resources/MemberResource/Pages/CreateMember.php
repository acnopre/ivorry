<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Models\Account;
use App\Models\Member;
use App\Models\MemberService;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $account = Account::find($data['account_id'] ?? null);

        if ($account && $account->plan_type === 'SHARED') {
            $this->createSharedFamily($data, $account);
            throw new Halt();
        }

        // INDIVIDUAL flow — auto-set member_type from coverage_type
        if ($account && $account->plan_type === 'INDIVIDUAL') {
            if ($account->coverage_type === 'ALL_PRINCIPAL') {
                $data['member_type'] = 'PRINCIPAL';
            } elseif ($account->coverage_type === 'ALL_DEPENDENT') {
                $data['member_type'] = 'DEPENDENT';
            }
        }

        // INDIVIDUAL flow
        if (! empty($data['coc_number'])) {
            $data['card_number'] = null;
        } else {
            $data['coc_number'] = null;
        }
        unset($data['use_coc_number']);

        $user = static::createUserForMember($data['first_name'], $data['last_name'], $data['email'] ?? null);
        $data['user_id'] = $user->id;

        if ($account && $account->mbl_type === 'Fixed') {
            $data['mbl_balance'] = $account->mbl_amount;
        }

        // Auto-set INACTIVE if expiration_date is in the past (before today)
        if (!empty($data['expiration_date']) && \Carbon\Carbon::parse($data['expiration_date'])->lt(today())) {
            $data['status'] = 'INACTIVE';
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $cardNumber = $this->record->card_number ?? $this->record->coc_number;
        if ($cardNumber && $this->record->account_id) {
            MemberService::initializeForCard($cardNumber, $this->record->account_id);
        }
    }

    protected function createSharedFamily(array $data, Account $account): void
    {
        $cardNumber   = $data['shared_card_number'] ?? null;
        $coverageType = $account->coverage_type ?? 'DEFAULT';

        if (! $cardNumber) {
            Notification::make()->title('Card number is required.')->danger()->send();
            return;
        }

        $existsOther = false;
        if ($existsOther) {
            Notification::make()->title('Card number already exists in a different account.')->danger()->send();
            return;
        }

        $sharedStatus = (!empty($account->expiration_date) && \Carbon\Carbon::parse($account->expiration_date)->lt(today()))
            ? 'INACTIVE' : 'ACTIVE';

        DB::beginTransaction();
        try {
            $count = 0;

            if ($coverageType === 'ALL_PRINCIPAL') {
                // All members are PRINCIPAL
                foreach ($data['dependents'] ?? [] as $dep) {
                    if (empty($dep['first_name']) || empty($dep['last_name'])) continue;
                    $user = static::createUserForMember($dep['first_name'], $dep['last_name'], $dep['email'] ?? null);
                    Member::create([
                        'account_id'  => $account->id,
                        'card_number' => $cardNumber,
                        'first_name'  => $dep['first_name'],
                        'last_name'   => $dep['last_name'],
                        'middle_name' => $dep['middle_name'] ?? null,
                        'suffix'      => $dep['suffix'] ?? null,
                        'member_type' => 'PRINCIPAL',
                        'birthdate'   => $dep['birthdate'] ?? null,
                        'gender'      => $dep['gender'] ?? null,
                        'email'       => $dep['email'] ?? null,
                        'phone'       => $dep['phone'] ?? null,
                        'status'      => $sharedStatus,
                        'user_id'     => $user->id,
                        'mbl_balance' => $account->mbl_type === 'Fixed' ? $account->mbl_amount : null,
                    ]);
                    $count++;
                }
                $summary = "{$count} principal(s) added with card {$cardNumber}.";

            } elseif ($coverageType === 'ALL_DEPENDENT') {
                // All members are DEPENDENT
                foreach ($data['dependents'] ?? [] as $dep) {
                    if (empty($dep['first_name']) || empty($dep['last_name'])) continue;
                    $user = static::createUserForMember($dep['first_name'], $dep['last_name'], $dep['email'] ?? null);
                    Member::create([
                        'account_id'  => $account->id,
                        'card_number' => $cardNumber,
                        'first_name'  => $dep['first_name'],
                        'last_name'   => $dep['last_name'],
                        'middle_name' => $dep['middle_name'] ?? null,
                        'suffix'      => $dep['suffix'] ?? null,
                        'member_type' => 'DEPENDENT',
                        'birthdate'   => $dep['birthdate'] ?? null,
                        'gender'      => $dep['gender'] ?? null,
                        'email'       => $dep['email'] ?? null,
                        'phone'       => $dep['phone'] ?? null,
                        'status'      => $sharedStatus,
                        'user_id'     => $user->id,
                        'mbl_balance' => $account->mbl_type === 'Fixed' ? $account->mbl_amount : null,
                    ]);
                    $count++;
                }
                $summary = "{$count} dependent(s) added with card {$cardNumber}.";

            } else {
                // DEFAULT: 1 principal + dependents
                $existsPrincipal = Member::where('card_number', $cardNumber)
                    ->where('account_id', $account->id)
                    ->where('member_type', 'PRINCIPAL')
                    ->whereNull('deleted_at')
                    ->exists();
                if ($existsPrincipal) {
                    Notification::make()->title('A principal with this card number already exists in this account.')->danger()->send();
                    return;
                }

                $principalUser = static::createUserForMember($data['principal_first_name'], $data['principal_last_name'], $data['principal_email'] ?? null);
                Member::create([
                    'account_id'  => $account->id,
                    'card_number' => $cardNumber,
                    'first_name'  => $data['principal_first_name'],
                    'last_name'   => $data['principal_last_name'],
                    'middle_name' => $data['principal_middle_name'] ?? null,
                    'suffix'      => $data['principal_suffix'] ?? null,
                    'member_type' => 'PRINCIPAL',
                    'birthdate'   => $data['principal_birthdate'] ?? null,
                    'gender'      => $data['principal_gender'] ?? null,
                    'email'       => $data['principal_email'] ?? null,
                    'phone'       => $data['principal_phone'] ?? null,
                    'status'      => $sharedStatus,
                    'user_id'     => $principalUser->id,
                    'mbl_balance' => $account->mbl_type === 'Fixed' ? $account->mbl_amount : null,
                ]);

                $depCount = 0;
                foreach ($data['dependents'] ?? [] as $dep) {
                    if (empty($dep['first_name']) || empty($dep['last_name'])) continue;
                    $depUser = static::createUserForMember($dep['first_name'], $dep['last_name'], $dep['email'] ?? null);
                    Member::create([
                        'account_id'  => $account->id,
                        'card_number' => $cardNumber,
                        'first_name'  => $dep['first_name'],
                        'last_name'   => $dep['last_name'],
                        'middle_name' => $dep['middle_name'] ?? null,
                        'suffix'      => $dep['suffix'] ?? null,
                        'member_type' => 'DEPENDENT',
                        'birthdate'   => $dep['birthdate'] ?? null,
                        'gender'      => $dep['gender'] ?? null,
                        'email'       => $dep['email'] ?? null,
                        'phone'       => $dep['phone'] ?? null,
                        'status'      => $sharedStatus,
                        'user_id'     => $depUser->id,
                        'mbl_balance' => $account->mbl_type === 'Fixed' ? $account->mbl_amount : null,
                    ]);
                    $depCount++;
                }
                $summary = "1 principal + {$depCount} dependent(s) added with card {$cardNumber}.";
            }

            MemberService::initializeForCard($cardNumber, $account->id);

            DB::commit();

            Notification::make()
                ->title('Members created successfully')
                ->body($summary)
                ->success()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));

            $this->redirect($this->getResource()::getUrl('index'));
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Error creating family')->body($e->getMessage())->danger()->send();
        }
    }

    public static function createUserForMember(string $firstName, string $lastName, ?string $email): User
    {
        if ($email && ($existing = User::where('email', $email)->first())) {
            if (!$existing->hasRole('Member')) {
                $existing->assignRole('Member');
            }
            return $existing;
        }

        $plainPassword = Str::random(12);
        $user = User::create([
            'name'     => "{$firstName} {$lastName}",
            'email'    => $email,
            'password' => Hash::make($plainPassword),
            'must_change_password' => true,
        ]);
        $user->assignRole('Member');

        if ($email) {
            Password::broker()->createToken($user);
            $user->notify(new \App\Notifications\SendGeneratedPassword($plainPassword));
        }

        return $user;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
