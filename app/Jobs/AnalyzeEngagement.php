<?php

namespace App\Jobs;

use App\Models\Engagement;
use App\Services\Ai\EstimateAssistant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Fait analyser par l'IA une demande reçue par l'estimateur, en tâche de fond : l'analyse
 * attend sur la fiche de la demande. Sans réponse d'un fournisseur, elle peut être relancée
 * depuis la fiche.
 */
class AnalyzeEngagement implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;

    public function __construct(public int $engagementId) {}

    public function handle(EstimateAssistant $assistant): void
    {
        $engagement = Engagement::query()->find($this->engagementId);

        if ($engagement !== null) {
            $assistant->analyze($engagement);
        }
    }
}
