<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Password;

class SendGeneratedPassword extends Notification
{
    public string $password;

    public function __construct(string $password)
    {
        $this->password = $password;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // Generate reset token for this user
        $token = Password::createToken($notifiable);

        return (new MailMessage)
            ->subject('Your New Member Account')
            ->markdown('emails.member.generated-password', [
                'name'     => $notifiable->name,
                'password' => $this->password,
                'loginUrl' => route('set-password', ['token' => $token, 'email' => $notifiable->email]),
            ]);
    }
}
