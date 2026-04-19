<?php

namespace App\Filament\Pages;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use App\Notifications\ResendSetPasswordLink;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Filament\Notifications\Notification;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        // Pre-fill email from cookie if remember me was previously checked
        $rememberedEmail = request()->cookie('remembered_email');
        if ($rememberedEmail) {
            $this->form->fill([
                'email'    => $rememberedEmail,
                'remember' => true,
            ]);
        }
    }

    public function authenticate(): ?LoginResponse
    {
        $formData = $this->form->getState();
        $remember = $formData['remember'] ?? false;

        $response = parent::authenticate();

        $user = Auth::user();

        // Save or clear the remembered email cookie
        if ($remember) {
            Cookie::queue('remembered_email', $formData['email'], 60 * 24 * 30); // 30 days
        } else {
            Cookie::queue(Cookie::forget('remembered_email'));
        }

        if ($user && $user->must_change_password) {
            $user->notify(new ResendSetPasswordLink());

            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

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
