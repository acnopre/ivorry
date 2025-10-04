<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class SetEmail extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.pages.set-email';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $layout = 'filament-panels::components.layout.simple';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'email' => auth()->user()?->email,
        ]);
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save()
    {
        $this->validate([
            'data.email' => ['required', 'email'],
        ]);

        $user = auth()->user();

        if (! $user) {
            Notification::make()
                ->title('You must be logged in as a member.')
                ->danger()
                ->send();

            return;
        }

        if (! empty($this->data['email']) && User::where('email', $this->data['email'])->where('id', '!=', $user->id)->exists()) {
            Notification::make()
                ->title('This email is already registered.')
                ->danger()
                ->send();

            throw new Halt(); 
        }
        $plainPassword = Str::random(12);

        $user->update([
            'email' => $this->data['email'],
            'password' => Hash::make($plainPassword),
            'must_change_password' => true,
        ]);


        // Generate password reset token only if email exists
        if (! empty($this->data['email'])) {
            $token = Password::broker()->createToken($user);

            // Send email with reset link + generated password
            $user->notify(new \App\Notifications\SendGeneratedPassword($plainPassword));
           
            Notification::make()
                ->title('Email updated successfully!')
                ->body('We’ve sent a password reset link to your new email. Please check your inbox.') 
                ->success() 
                ->send();
        }

    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function isAccessibleWithoutAuthentication(): bool
    {
        return false; // only logged-in members can see this
    }
    public function hasLogo(): bool
    {
        return false;
    }

}
