<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FcmService — Envoi de notifications push via Firebase Cloud Messaging
 *
 * Utilise l'API Legacy FCM (HTTP v1 compatible).
 * Configurer FCM_SERVER_KEY dans .env pour activer les push.
 */
class FcmService
{
    private const FCM_URL = 'https://fcm.googleapis.com/fcm/send';

    /**
     * Envoie une notification push à une liste de tokens FCM.
     *
     * @param  array  $tokens   Liste des FCM tokens des appareils
     * @param  string $title    Titre de la notification
     * @param  string $body     Corps du message
     * @param  array  $data     Données supplémentaires (payload)
     */
    public function send(array $tokens, string $title, string $body, array $data = []): void
    {
        $serverKey = config('services.fcm.server_key');

        if (empty($serverKey) || empty($tokens)) {
            Log::info('FCM: pas de server key configurée ou aucun token. Notification DB uniquement.');
            return;
        }

        // Découper en lots de 500 (limite FCM)
        $chunks = array_chunk($tokens, 500);

        foreach ($chunks as $chunk) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $serverKey,
                    'Content-Type'  => 'application/json',
                ])->post(self::FCM_URL, [
                    'registration_ids' => $chunk,
                    'notification'     => [
                        'title' => $title,
                        'body'  => $body,
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                    'data'             => array_merge($data, [
                        'title' => $title,
                        'body'  => $body,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]),
                    'priority'         => 'high',
                    'content_available' => true,
                ]);

                if ($response->failed()) {
                    Log::error('FCM send failed', [
                        'status'   => $response->status(),
                        'response' => $response->json(),
                    ]);
                } else {
                    Log::info('FCM envoyé', [
                        'tokens_count' => count($chunk),
                        'success'      => $response->json('success'),
                        'failure'      => $response->json('failure'),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('FCM exception : ' . $e->getMessage());
            }
        }
    }

    /**
     * Envoie à un seul token.
     */
    public function sendToOne(string $token, string $title, string $body, array $data = []): void
    {
        $this->send([$token], $title, $body, $data);
    }
}
