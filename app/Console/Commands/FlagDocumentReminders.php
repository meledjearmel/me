<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotification;
use App\Services\CommercialDocuments;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Signale chaque matin les devis sans réponse et les factures en retard à relancer, et prévient
 * le développeur. Rien n'est envoyé au client : il relance lui-même, avec un message relu.
 */
#[Signature('documents:flag-reminders')]
#[Description('Signale les devis sans réponse et les factures en retard à relancer')]
class FlagDocumentReminders extends Command
{
    public function handle(CommercialDocuments $documents): int
    {
        $flagged = $documents->flagReminders();

        if ($flagged !== []) {
            $count = count($flagged);
            SendPushNotification::dispatch(
                $count > 1 ? "{$count} relances à envoyer" : 'Une relance à envoyer',
                collect($flagged)->map(fn ($document): string => "{$document->number} · ".($document->client_company ?? $document->client_name))->implode(', '),
                ['type' => 'commercial_document', 'id' => (string) $flagged[0]->id],
            );
        }

        $this->info(count($flagged).' relance(s) signalée(s).');

        return self::SUCCESS;
    }
}
