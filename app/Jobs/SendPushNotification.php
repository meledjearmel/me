<?php

namespace App\Jobs;

use App\Services\PushNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Throwable;

/**
 * Prévient l'app mobile d'administration d'un nouvel élément à traiter (message,
 * demande de collaboration, avis), en tâche de fond : voir PushNotifier pour l'envoi.
 */
class SendPushNotification implements ShouldQueue
{
    use Dispatchable, Queueable;

    /** Un seul essai : un échec est déjà journalisé par PushNotifier, pas la peine de réessayer une notification devenue obsolète. */
    public int $tries = 1;

    /**
     * @param  array<string, string>  $data
     */
    public function __construct(
        public string $title,
        public string $body,
        public array $data = [],
    ) {}

    public function handle(): void
    {
        // Le paquet Firebase (kreait/laravel-firebase) n'est pas forcément installé : sans lui,
        // ce job ne doit jamais faire échouer la file d'attente, juste rester sans effet.
        if (! interface_exists(Messaging::class)) {
            Log::warning('Notification push ignorée : le paquet Firebase n\'est pas installé.');

            return;
        }

        try {
            // Résoudre PushNotifier échoue tant que FIREBASE_CREDENTIALS n'est pas configuré
            // (projet Firebase introuvable) : à journaliser comme un échec d'envoi, jamais
            // comme une erreur qui casse la requête publique qui a déclenché la notification.
            app(PushNotifier::class)->notify($this->title, $this->body, $this->data);
        } catch (Throwable $exception) {
            Log::error('Notification push impossible : Firebase n\'est pas configuré ou indisponible.', ['error' => $exception->getMessage()]);
        }
    }
}
