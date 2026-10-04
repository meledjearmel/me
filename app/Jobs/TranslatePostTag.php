<?php

namespace App\Jobs;

use App\Models\PostTag;
use App\Services\Ai\TextAssistService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Traduit en anglais le nom d'un tag du blog créé en français. Le nom anglais
 * n'est remplacé que s'il recopie encore le français : une correction faite
 * à la main dans l'administration n'est jamais écrasée.
 */
class TranslatePostTag implements ShouldQueue
{
    use Dispatchable, Queueable;

    /** Un seul essai : TextAssistService parcourt déjà plusieurs fournisseurs. */
    public int $tries = 1;

    public function __construct(public PostTag $tag) {}

    public function handle(TextAssistService $assistant): void
    {
        $french = $this->tag->getTranslation('name', 'fr');

        if ($this->tag->getTranslation('name', 'en', false) !== $french) {
            return;
        }

        $english = $assistant->translate($french, 'fr', 'en');

        if (blank($english) || mb_strlen($english) > 40 || str_contains($english, "\n")) {
            return;
        }

        $this->tag->setTranslation('name', 'en', $english)->save();
    }
}
