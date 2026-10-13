<?php

namespace App\Services\Ai;

use App\Ai\Agents\HtmlTranslator;
use App\Ai\Agents\PostWriter;
use App\Ai\Agents\ProjectDrafter;
use App\Ai\Agents\TechnologyDescriber;
use App\Ai\Agents\TextImprover;
use App\Ai\Agents\TextTranslator;
use App\Enums\TextTone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Agent;
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

    /** Traduit un fragment HTML d'article en conservant ses balises. */
    public function translateHtml(string $html, string $sourceLocale, string $targetLocale): ?string
    {
        return $this->attempt(
            fn (string $provider, int $timeout, ?string $model): string => $this->unwrapHtml((new HtmlTranslator($sourceLocale, $targetLocale))
                ->prompt($html, provider: $provider, model: $model, timeout: $timeout)->text),
        );
    }

    /**
     * Réécrit la sélection d'un article, ou rédige le passage à insérer au curseur, en HTML.
     *
     * @param  array{title?: string|null, excerpt?: string|null, selection?: string|null, context?: string|null}  $article
     */
    public function writePost(string $instruction, string $locale, array $article): ?string
    {
        $selection = $article['selection'] ?? null;
        $context = $article['context'] ?? null;

        $prompt = match (true) {
            filled($selection) => "<selection>{$selection}</selection>\n\nConsigne : {$instruction}",
            filled($context) => "<contexte>{$context}</contexte>\n\nConsigne : {$instruction}",
            default => "Consigne : {$instruction}",
        };

        return $this->attempt(
            fn (string $provider, int $timeout, ?string $model): string => $this->unwrapHtml((new PostWriter($locale, $article['title'] ?? null, $article['excerpt'] ?? null, filled($selection)))
                ->prompt($prompt, provider: $provider, model: $model, timeout: $timeout)->text),
        );
    }

    /** Retire le bloc de code Markdown (```html … ```) dont certains modèles entourent leur HTML. */
    private function unwrapHtml(string $text): string
    {
        return trim((string) preg_replace('/^```(?:html)?\s*|\s*```$/i', '', trim($text)));
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
     * Propose, en français, les champs d'un projet à partir d'une source. Le JSON du
     * modèle est ramené aux limites du formulaire, et les relations aux identifiants
     * du catalogue ; une réponse illisible ou sans titre ni contexte passe au fournisseur
     * suivant.
     *
     * @param  array{technologies: array<int, string>, domains: array<int, string>, job_profiles: array<int, string>}  $catalog
     * @return array<string, mixed>|null
     */
    public function draftProject(string $source, array $catalog): ?array
    {
        $json = $this->attempt(function (string $provider, int $timeout, ?string $model) use ($source, $catalog): string {
            $draft = $this->normalizeProjectDraft(
                $this->decodeJson((new ProjectDrafter($catalog))->prompt($source, provider: $provider, model: $model, timeout: $timeout)->text),
                $catalog,
            );

            return $draft === null ? '' : (string) json_encode($draft);
        }, 'ai.text_assist.draft');

        return $json === null ? null : json_decode($json, true);
    }

    /**
     * Fait répondre un agent en JSON, avec le même repli sur les fournisseurs : la réponse
     * passe par $normalize, et un résultat null (illisible, incomplet) passe au suivant.
     *
     * @param  callable(array<string, mixed>): ?array<string, mixed>  $normalize
     * @return array<string, mixed>|null
     */
    public function promptJson(Agent $agent, string $prompt, callable $normalize, string $limits = 'ai.text_assist.draft'): ?array
    {
        $json = $this->attempt(function (string $provider, int $timeout, ?string $model) use ($agent, $prompt, $normalize): string {
            $decoded = $this->decodeJson($agent->prompt($prompt, provider: $provider, model: $model, timeout: $timeout)->text);
            $result = $decoded === null ? null : $normalize($decoded);

            return $result === null ? '' : (string) json_encode($result);
        }, $limits);

        return $json === null ? null : json_decode($json, true);
    }

    /** @return array<string, mixed>|null */
    private function decodeJson(string $text): ?array
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false) {
            return null;
        }

        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>|null  $draft
     * @param  array{technologies: array<int, string>, domains: array<int, string>, job_profiles: array<int, string>}  $catalog
     * @return array<string, mixed>|null
     */
    private function normalizeProjectDraft(?array $draft, array $catalog): ?array
    {
        $text = fn (mixed $value, ?int $max = null): ?string => is_string($value) && trim($value) !== ''
            ? ($max === null ? trim($value) : mb_substr(trim($value), 0, $max))
            : null;
        $month = fn (mixed $value): ?string => is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) === 1 ? $value : null;
        $ids = fn (string $key): array => array_values(array_unique(array_filter(
            (array) ($draft[$key] ?? []),
            fn (mixed $id): bool => is_int($id) && array_key_exists($id, $catalog[$key]),
        )));

        $title = $text($draft['title'] ?? null, 255);
        $context = $text($draft['context'] ?? null);

        if ($title === null || $context === null) {
            return null;
        }

        $teamSize = $draft['team_size'] ?? null;

        return [
            'title' => $title,
            'slug' => Str::slug($text($draft['slug'] ?? null, 255) ?? $title),
            'tagline' => $text($draft['tagline'] ?? null, 160),
            'role' => $text($draft['role'] ?? null, 120),
            'client' => $text($draft['client'] ?? null, 120),
            'platform' => $text($draft['platform'] ?? null, 120),
            'context' => $context,
            'challenges' => $text($draft['challenges'] ?? null),
            'realization' => $text($draft['realization'] ?? null),
            'result' => $text($draft['result'] ?? null),
            'decisions' => collect(is_array($draft['decisions'] ?? null) ? $draft['decisions'] : [])
                ->map(fn (mixed $decision): ?array => is_array($decision) && $text($decision['choice'] ?? null) && $text($decision['reason'] ?? null)
                    ? ['choice' => $text($decision['choice'], 160), 'reason' => $text($decision['reason'], 600)]
                    : null)
                ->filter()->take(6)->values()->all(),
            'key_figures' => collect(is_array($draft['key_figures'] ?? null) ? $draft['key_figures'] : [])
                ->map(fn (mixed $figure): ?array => is_array($figure) && $text((string) ($figure['value'] ?? '')) && $text($figure['label'] ?? null)
                    ? ['value' => $text((string) $figure['value'], 20), 'label' => $text($figure['label'], 80)]
                    : null)
                ->filter()->take(4)->values()->all(),
            'started_on' => $month($draft['started_on'] ?? null),
            'ended_on' => $month($draft['ended_on'] ?? null),
            'team_size' => is_int($teamSize) && $teamSize >= 1 && $teamSize <= 200 ? $teamSize : null,
            'technologies' => $ids('technologies'),
            'domains' => $ids('domains'),
            'job_profiles' => $ids('job_profiles'),
        ];
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
     * @param  string  $limits  Bloc de config qui porte « timeout » et « total_budget ».
     */
    private function attempt(callable $call, string $limits = 'ai.text_assist'): ?string
    {
        $budget = (float) config("{$limits}.total_budget");
        $minimumTimeout = (int) config('ai.text_assist.min_provider_timeout');
        $maxTimeout = (int) config("{$limits}.timeout");
        $start = microtime(true);
        $tried = [];

        // Requêtes web seulement : en console (tests, workers de file, qui ont leur propre
        // délai par tâche), la limite resterait posée sur tout le processus, et sous Windows
        // elle compte le temps réel, ce qui coupait la suite de tests en cours de route.
        if (! app()->runningInConsole()) {
            set_time_limit((int) ceil($budget) + self::TIME_LIMIT_MARGIN);
        }

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
