<?php

namespace App\Services\Ai;

use App\Ai\Agents\TextImprover;
use App\Ai\Agents\TextTranslator;
use App\Enums\TextTone;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Traduction et amélioration de texte via IA, avec repli sur plusieurs
 * fournisseurs (voir config/ai.php: text_assist.providers).
 */
class TextAssistService
{
    public function translate(string $text, string $sourceLocale, string $targetLocale): ?string
    {
        return $this->attempt(
            fn (string $provider): ?string => (new TextTranslator($sourceLocale, $targetLocale))
                ->prompt($text, provider: $provider)['translation'] ?? null,
        );
    }

    public function improve(string $text, string $locale, ?TextTone $tone, ?string $instructions): ?string
    {
        return $this->attempt(
            fn (string $provider): ?string => (new TextImprover($locale, $tone, $instructions))
                ->prompt($text, provider: $provider)['text'] ?? null,
        );
    }

    /**
     * Parcourt la chaîne de fournisseurs : un modèle retiré, en quota ou qui
     * répond vide passe simplement au suivant (même choix que ChatController).
     */
    private function attempt(callable $call): ?string
    {
        foreach (config('ai.text_assist.providers') as $provider) {
            try {
                $result = $call($provider);
            } catch (Throwable $exception) {
                Log::warning("Assistance IA : échec de [{$provider}] : ".$exception->getMessage());

                continue;
            }

            if (filled($result)) {
                return $result;
            }

            Log::warning("Assistance IA : réponse vide de [{$provider}].");
        }

        return null;
    }
}
