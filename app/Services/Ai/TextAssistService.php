<?php

namespace App\Services\Ai;

use App\Ai\Agents\TechnologyDescriber;
use App\Ai\Agents\TextImprover;
use App\Ai\Agents\TextTranslator;
use App\Enums\TextTone;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Traduction et amélioration de texte via IA, avec repli sur plusieurs
 * fournisseurs (voir config/ai.php: text_assist.providers), dans la limite
 * d'un budget de temps global (text_assist.total_budget).
 */
class TextAssistService
{
    /** Marge accordée à PHP au-delà du budget pour finir proprement la requête. */
    private const TIME_LIMIT_MARGIN = 10;

    public function translate(string $text, string $sourceLocale, string $targetLocale): ?string
    {
        return $this->attempt(
            fn (string $provider, int $timeout, ?string $model): string => (new TextTranslator($sourceLocale, $targetLocale))
                ->prompt($text, provider: $provider, model: $model, timeout: $timeout)->text,
        );
    }

    public function improve(string $text, string $locale, ?TextTone $tone, ?string $instructions): ?string
    {
        return $this->attempt(
            fn (string $provider, int $timeout, ?string $model): string => (new TextImprover($locale, $tone, $instructions))
                ->prompt($text, provider: $provider, model: $model, timeout: $timeout)->text,
        );
    }

    /** Longueur maximale d'une description de technologie (champ admin). */
    private const TECHNOLOGY_DESCRIPTION_MAX_LENGTH = 150;

    /**
     * Rédige la description d'une technologie en français, puis la traduit en
     * anglais ; null si l'une des deux étapes échoue.
     * Une réponse sur plusieurs lignes, trop longue (un modèle qui livre son
     * raisonnement au lieu de la phrase) ou sans ponctuation finale (coupée
     * par la limite de jetons) est traitée comme vide, pour passer au
     * fournisseur suivant.
     *
     * @return array{fr: string, en: string}|null
     */
    public function describeTechnology(string $name, ?string $category): ?array
    {
        $prompt = filled($category) ? "{$name} (catégorie : {$category})" : $name;

        $french = $this->attempt(function (string $provider, int $timeout, ?string $model) use ($prompt): string {
            $text = $this->clean((new TechnologyDescriber)
                ->prompt($prompt, provider: $provider, model: $model, timeout: $timeout)->text);

            return $this->isCompleteShortSentence($text) ? $text : '';
        });

        if ($french === null) {
            return null;
        }

        $english = $this->translate($french, 'fr', 'en');

        return $english === null ? null : ['fr' => $french, 'en' => $english];
    }

    /**
     * Parcourt la chaîne de fournisseurs : un modèle retiré, en quota ou qui
     * répond vide passe simplement au suivant (même choix que ChatController).
     * Chaque entrée de la chaîne est « fournisseur » (modèle par défaut de son
     * bloc) ou « fournisseur:modèle » pour cibler un autre modèle du même
     * fournisseur ; le premier qui a du quota répond.
     * Le timeout de chaque appel est plafonné au temps restant du budget global,
     * pour répondre en 503 JSON avant que PHP ou le proxy ne coupe la requête.
     *
     * @param  callable(string, int, ?string): string  $call
     */
    private function attempt(callable $call): ?string
    {
        $budget = (float) config('ai.text_assist.total_budget');
        $minimumTimeout = (int) config('ai.text_assist.min_provider_timeout');
        $maxTimeout = (int) config('ai.text_assist.timeout');
        $start = microtime(true);
        $tried = [];

        set_time_limit((int) ceil($budget) + self::TIME_LIMIT_MARGIN);

        foreach (config('ai.text_assist.providers') as $entry) {
            [$provider, $model] = array_pad(explode(':', $entry, 2), 2, null);
            $remaining = $budget - (microtime(true) - $start);

            if ($remaining < $minimumTimeout) {
                $tried[] = "{$entry} (ignoré : budget épuisé)";

                continue;
            }

            $timeout = min($maxTimeout, (int) floor($remaining));
            $providerStart = microtime(true);

            try {
                $result = $this->clean($call($provider, $timeout, $model));
            } catch (Throwable $exception) {
                $duration = $this->elapsed($providerStart);
                $tried[] = "{$entry} ({$duration} s : échec)";
                Log::warning("Assistance IA : échec de [{$entry}] après {$duration} s : ".$exception->getMessage());

                continue;
            }

            if ($result !== '') {
                return $result;
            }

            $duration = $this->elapsed($providerStart);
            $tried[] = "{$entry} ({$duration} s : vide)";
            Log::warning("Assistance IA : réponse vide de [{$entry}] après {$duration} s.");
        }

        Log::error('Assistance IA : tous les fournisseurs ont échoué en '.$this->elapsed($start).' s (budget '.$budget.' s) : '.implode(', ', $tried));

        return null;
    }

    private function isCompleteShortSentence(string $text): bool
    {
        return ! str_contains($text, "\n")
            && mb_strlen($text) <= self::TECHNOLOGY_DESCRIPTION_MAX_LENGTH
            && preg_match('/[.!?…]$/u', $text) === 1;
    }

    private function elapsed(float $since): string
    {
        return number_format(microtime(true) - $since, 1, '.', '');
    }

    /**
     * Retire les espaces et les guillemets englobants que les modèles ajoutent
     * parfois autour d'une réponse en texte brut.
     */
    private function clean(string $text): string
    {
        $text = trim($text);

        if (preg_match('/^(["«“])(.*)(["»”])$/su', $text, $matches) === 1 && ! str_contains($matches[2], $matches[1])) {
            $text = trim($matches[2]);
        }

        return $text;
    }
}
