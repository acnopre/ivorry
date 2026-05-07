<?php

namespace App\Filament\Resources\DependentMemberResource\Pages;

use App\Filament\Resources\DependentMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use App\Models\MemberService;

class CreateDependentMember extends CreateRecord
{
    protected static string $resource = DependentMemberResource::class;

    protected function afterCreate(): void
    {
        $member = $this->record;
        if ($member->card_number && $member->account_id) {
            MemberService::initializeForCard($member->card_number, $member->account_id);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $plainPassword = Str::random(12);

        if (!empty($data['email']) && ($existing = User::where('email', $data['email'])->first())) {
            if (!$existing->hasRole('Member')) {
                $existing->assignRole('Member');
            }
            $data['user_id'] = $existing->id;
            return $data;
        }

        $user = User::create([
            'name'     => $data['first_name'] . ' ' . $data['last_name'],
            'email'    => $data['email'] ?? null,
            'password' => Hash::make($plainPassword),
            'must_change_password' => true,
        ]);

        if (! empty($data['email'])) {
            Password::broker()->createToken($user);
            $user->notify(new \App\Notifications\SendGeneratedPassword($plainPassword));
        }

        $data['user_id'] = $user->id;
        $user->assignRole('Member');

        return $data;
    }
}
