<?php

namespace App\Services;

use App\Models\PushToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

/**
 * Notifications push (FCM) vers l'app mobile d'administration. Comme les emails
 * (voir SendEngagementMails) : un échec est journalisé et ne remonte jamais, l'envoi
 * n'est jamais sur le chemin critique d'une requête publique.
 */
class PushNotifier
{
    public function __construct(private readonly Messaging $messaging) {}

    /**
     * @param  array<string, string>  $data  données arbitraires jointes à la notification (ex. `['type' => 'contact', 'id' => '12']`)
     */
    public function notify(string $title, string $body, array $data = []): void
    {
        $tokens = PushToken::query()->pluck('token', 'id');

        if ($tokens->isEmpty()) {
            return;
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $report = $this->messaging->sendMulticast($message, $tokens->values()->all());

            $this->forgetDeadTokens($tokens, $report);
        } catch (Throwable $exception) {
            Log::error('Envoi de notification push impossible', ['error' => $exception->getMessage()]);
        }
    }

    /**
     * Un jeton refusé par FCM comme invalide ou désinscrit (app supprimée) ne resservira
     * plus : on l'oublie, pour ne pas continuer à le solliciter à chaque envoi.
     *
     * @param  Collection<int, string>  $tokens
     */
    private function forgetDeadTokens(Collection $tokens, MulticastSendReport $report): void
    {
        $dead = collect([...$report->invalidTokens(), ...$report->unknownTokens()]);

        if ($dead->isEmpty()) {
            return;
        }

        $ids = $tokens->filter(fn (string $token): bool => $dead->contains($token))->keys();

        PushToken::query()->whereKey($ids)->delete();
    }
}
