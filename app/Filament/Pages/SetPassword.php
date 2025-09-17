<?php

// app/Filament/Pages/Auth/SetPassword.php
namespace App\Filament\Pages\Auth;

use Filament\Pages\Page;
use Filament\Forms;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SetPassword extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;
    protected static ?string $navigationIcon = null;
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.set-password';
    protected static ?string $title = 'Set Your Password';

    public $email;
    public $token;
    public $password;
    public $password_confirmation;

    public function mount()
    {
        $this->email = request('email');
        $this->token = request()->route('token');
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('password')
                ->label('New Password')
                ->password()
                ->required()
                ->confirmed(),
            Forms\Components\TextInput::make('password_confirmation')
                ->label('Confirm Password')
                ->password()
                ->required(),
        ]);
    }

    public function submit()
    {
        $status = Password::reset(
            [
                'email'                 => $this->email,
                'password'              => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token'                 => $this->token,
            ],
            function (User $user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('success', 'Password set successfully! You may now log in.');
            return redirect()->route('filament.admin.auth.login');
        }

        $this->addError('email', __($status));
    }
}
