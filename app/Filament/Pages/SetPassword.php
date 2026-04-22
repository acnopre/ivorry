<?php

namespace App\Filament\Pages;

use App\Models\Clinic;
use App\Models\User;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;
use App\Notifications\SendGeneratedPassword;

class SetPassword extends Page implements Forms\Contracts\HasForms
{

    protected static string $view = 'filament.pages.set-password';
    protected static string $layout = 'filament-panels::components.layout.simple';

    public ?array $data = [];
    public ?string $email = null;
    public ?string $token = null;

    public function mount(string $token): void
    {
        $this->email = request()->query('email');
        $this->token = $token;

        $this->form->fill();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('current_password')
                    ->label('Temporary Password')
                    ->password()
                    ->revealable()
                    ->required(),

                Forms\Components\TextInput::make('password')
                    ->label('New Password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->confirmed(),

                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirm New Password')
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $this->validate([
            'data.current_password' => ['required'],
            'data.password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = User::where('email', $this->email)->firstOrFail();

        if (! $user) {
            Notification::make()
                ->title('Invalid email address.')
                ->danger()
                ->send();
            return;
        }

        if (! Password::broker()->tokenExists($user, $this->token)) {
            // Token expired — generate new password and resend
            $newPassword = Str::random(12);

            $user->update([
                'password' => Hash::make($newPassword),
                'must_change_password' => true,
            ]);

            $user->notify(new SendGeneratedPassword($newPassword));

            Notification::make()
                ->title('Link Expired')
                ->body('Your password reset link has expired. A new temporary password has been sent to your email. Please check your inbox.')
                ->warning()
                ->persistent()
                ->send();

            $this->form->fill();
            return;
        }

        if (! Hash::check($this->data['current_password'], $user->password)) {
            Notification::make()
                ->title('The provided temporary password is incorrect.')
                ->danger()
                ->send();
            return;
        }


        $user->update([
            'password' => Hash::make($this->data['password']),
            'must_change_password' => false,
        ]);

        Clinic::where('user_id', $user->id)->update(['welcome_email_status' => 'password_set']);

        Password::broker()->deleteToken($user);

        Notification::make()
            ->title('Password Set Successfully')
            ->body('Your password has been updated. Please log in with your new password.')
            ->success()
            ->send();

        $this->redirectRoute('filament.admin.auth.login');
    }


    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function isAccessibleWithoutAuthentication(): bool
    {
        return true;
    }

    public function hasLogo(): bool
    {
        return false;
    }
}
