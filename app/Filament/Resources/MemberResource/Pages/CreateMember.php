<?php

// app/Filament/Resources/MemberResource/Pages/CreateMember.php
namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $memberRole = 'Member';
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'name'     => $data['name'],
                'password' => bcrypt(Str::random(16)),
            ]
        );

        // Send password reset link to email
        Password::sendResetLink(['email' => $user->email]);

        // Link user to member
        $data['user_id'] = $user->id;
        $user->assignRole($memberRole);

        return $data;
    }
}
