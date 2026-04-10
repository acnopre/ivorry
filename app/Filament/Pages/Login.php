<?php

namespace App\Filament\Pages;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use App\Notifications\ResendSetPasswordLink;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        $user = Auth::user();

        if ($user && $user->must_change_password) {
            $user->notify(new ResendSetPasswordLink());

            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            Notification::make()
                ->title('Password Change Required')
                ->body('A set-password link has been sent to your email. Please check your inbox.')
                ->warning()
                ->persistent()
                ->send();

            $this->form->fill();

            return null;
        }

        return $response;
    }
}
