<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

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
                    ->required(),

                Forms\Components\TextInput::make('password')
                    ->label('New Password')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->confirmed(),

                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirm New Password')
                    ->password()
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
            Notification::make()
                ->title('This password reset link is invalid or expired.')
                ->danger()
                ->send();
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

        Password::broker()->deleteToken($user);
        Auth::login($user);
        // store a flash session variable
        session()->flash('password_updated', true);

        $this->redirectRoute('filament.admin.pages.dashboard');
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
