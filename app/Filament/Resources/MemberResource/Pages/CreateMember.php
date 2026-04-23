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

        // INDIVIDUAL flow
        if (! empty($data['coc_number'])) {
            $data['card_number'] = null;
        } else {
            $data['coc_number'] = null;
        }
        unset($data['use_coc_number']);

        if (! empty($data['email']) && User::where('email', $data['email'])->exists()) {
            Notification::make()->title('This email is already registered.')->danger()->send();
            throw new Halt();
        }

        $user = static::createUserForMember($data['first_name'], $data['last_name'], $data['email'] ?? null);
        $data['user_id'] = $user->id;

        if ($account && $account->mbl_type === 'Fixed') {
            $data['mbl_balance'] = $account->mbl_amount;
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
        $cardNumber = $data['shared_card_number'] ?? null;

        if (! $cardNumber) {
            Notification::make()->title('Card number is required.')->danger()->send();
            return;
        }

        $existsOther = Member::where('card_number', $cardNumber)
            ->where('account_id', '!=', $account->id)
            ->whereNull('deleted_at')
            ->exists();
        if ($existsOther) {
            Notification::make()->title('Card number already exists in a different account.')->danger()->send();
            return;
        }

        $existsPrincipal = Member::where('card_number', $cardNumber)
            ->where('account_id', $account->id)
            ->where('member_type', 'PRINCIPAL')
            ->whereNull('deleted_at')
            ->exists();
        if ($existsPrincipal) {
            Notification::make()->title('A principal with this card number already exists in this account.')->danger()->send();
            return;
        }

        DB::beginTransaction();
        try {
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
                'status'      => 'ACTIVE',
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
                    'status'      => 'ACTIVE',
                    'user_id'     => $depUser->id,
                    'mbl_balance' => $account->mbl_type === 'Fixed' ? $account->mbl_amount : null,
                ]);
                $depCount++;
            }

            MemberService::initializeForCard($cardNumber, $account->id);

            DB::commit();

            Notification::make()
                ->title('Family created successfully')
                ->body("1 principal + {$depCount} dependent(s) added with card {$cardNumber}.")
                ->success()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Error creating family')->body($e->getMessage())->danger()->send();
        }
    }

    protected static function createUserForMember(string $firstName, string $lastName, ?string $email): User
    {
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
