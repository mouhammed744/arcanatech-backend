<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * ScheduleNotification — Notifie un étudiant d'une nouvelle séance ou modification.
 *
 * Canal utilisé : database (stocké en BDD, récupéré au login ou en temps réel)
 */
class ScheduleNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly string $type,      // 'schedule_created' | 'schedule_updated'
        public readonly array  $payload,   // données brutes (cours, jour, heure, salle…)
    ) {}

    /**
     * Canal : base de données uniquement.
     * FCM est géré séparément via FcmService pour plus de contrôle.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Données sauvegardées dans la table notifications.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => $this->title,
            'body'    => $this->body,
            'type'    => $this->type,
            'payload' => $this->payload,
        ];
    }
}
