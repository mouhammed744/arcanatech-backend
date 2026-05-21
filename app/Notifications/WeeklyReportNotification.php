<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WeeklyReportNotification extends Notification
{
    use Queueable;

    public function __construct(
        private array $report,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[ARCANA] Rapport hebdomadaire - {$this->report['university']}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Voici le rapport hebdomadaire de présence pour **{$this->report['university']}**.")
            ->line("**Période** : {$this->report['period']}")
            ->line('---')
            ->line("**Statistiques de présence**")
            ->line("- Présents : {$this->report['present']}")
            ->line("- En retard : {$this->report['late']}")
            ->line("- Absents : {$this->report['absent']}")
            ->line("- **Taux de présence : {$this->report['presence_rate']}%**")
            ->line('---')
            ->line("**Scans RFID**")
            ->line("- Total scans : {$this->report['total_scans']}")
            ->line("- Accès accordés : {$this->report['scans_granted']}")
            ->line("- Accès refusés : {$this->report['scans_refused']}");

        if (!empty($this->report['top_absent'])) {
            $mail->line('---');
            $mail->line('**Top étudiants les plus absents :**');
            foreach ($this->report['top_absent'] as $student) {
                $mail->line("- {$student['name']} ({$student['registration_number']}) : {$student['absence_count']} absences");
            }
        }

        $mail->action('Accéder au dashboard', config('app.frontend_url', config('app.url')))
             ->salutation('Cordialement, ARCANA TECH');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'weekly_report',
            'university' => $this->report['university'],
            'period' => $this->report['period'],
            'presence_rate' => $this->report['presence_rate'],
            'total_records' => $this->report['total_records'],
            'present' => $this->report['present'],
            'late' => $this->report['late'],
            'absent' => $this->report['absent'],
            'total_students' => $this->report['total_students'],
        ];
    }
}
