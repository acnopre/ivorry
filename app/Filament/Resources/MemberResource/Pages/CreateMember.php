<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $memberRole = 'Member';
        $plainPassword = Str::random(12);

        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name'     => $data['name'],
                'password' => Hash::make($plainPassword),
                'must_change_password' => true,
            ]
        );

        // Generate password reset token
        $token = Password::broker()->createToken($user);

        // Send email with reset link + generated password
        $user->notify(new \App\Notifications\SendGeneratedPassword($plainPassword));


        // Link user to member
        $data['user_id'] = $user->id;
        $user->assignRole($memberRole);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
