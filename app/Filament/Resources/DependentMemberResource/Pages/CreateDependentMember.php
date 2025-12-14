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
use Filament\Support\Exceptions\Halt;

class CreateDependentMember extends CreateRecord
{
    protected static string $resource = DependentMemberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['email']) && User::where('email', $data['email'])->exists()) {
            Notification::make()
                ->title('This email is already registered.')
                ->danger()
                ->send();

            // Stop the create process without crashing
            throw new Halt();
        }

        $memberRole = 'Member';
        $plainPassword = Str::random(12);
        $user = User::create([
            'name'     => $data['first_name'] . ' ' . $data['last_name'],
            'email'    => $data['email'] ?? null, // accept null
            'password' => Hash::make($plainPassword),
            'must_change_password' => true,
        ]);

        // Generate password reset token only if email exists
        if (! empty($data['email'])) {
            $token = Password::broker()->createToken($user);

            // Send email with reset link + generated password
            $user->notify(new \App\Notifications\SendGeneratedPassword($plainPassword));
        }

        // Link user to member
        $data['user_id'] = $user->id;
        $user->assignRole($memberRole);

        return $data;
    }
}
