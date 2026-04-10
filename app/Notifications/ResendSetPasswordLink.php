<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Password;

class ResendSetPasswordLink extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $token = Password::createToken($notifiable);

        return (new MailMessage)
            ->subject('Set Your Password')
            ->markdown('emails.member.resend-set-password', [
                'name' => $notifiable->name,
                'loginUrl' => route('app/set-password', ['token' => $token, 'email' => $notifiable->email]),
            ]);
    }
}
