<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Password;

class WelcomeClinicEmail extends Notification
{
    public function __construct(public string $tempPassword) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $token = Password::createToken($notifiable);

        return (new MailMessage)
            ->subject('Welcome to IVORRY')
            ->markdown('emails.clinic.welcome', [
                'name' => $notifiable->name,
                'tempPassword' => $this->tempPassword,
                'setPasswordUrl' => route('app/set-password', ['token' => $token, 'email' => $notifiable->email]),
            ]);
    }
}
