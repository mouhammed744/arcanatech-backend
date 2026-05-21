<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'UniAccess');

        return (new MailMessage)
            ->subject("Votre code de verification {$appName}")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line('Voici votre code de verification a usage unique :')
            ->line("**{$this->code}**")
            ->line('Ce code expire dans 10 minutes.')
            ->line('Si vous n avez pas tente de vous connecter, ignorez ce message et changez votre mot de passe.')
            ->salutation("L equipe {$appName}");
    }
}
