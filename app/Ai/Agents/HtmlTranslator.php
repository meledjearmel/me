<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Traduit un fragment HTML d'article du blog entre français et anglais, balises comprises.
 */
#[MaxTokens(4000)]
#[Temperature(0.2)]
class HtmlTranslator implements Agent
{
    use Promptable;

    private const LANGUAGES = ['fr' => 'français', 'en' => 'anglais'];

    public function __construct(
        private readonly string $sourceLocale,
        private readonly string $targetLocale,
    ) {}

    public function timeout(): int
    {
        return (int) config('ai.text_assist.timeout');
    }

    public function instructions(): Stringable|string
    {
        $from = self::LANGUAGES[$this->sourceLocale] ?? $this->sourceLocale;
        $to = self::LANGUAGES[$this->targetLocale] ?? $this->targetLocale;

        return <<<PROMPT
        Tu traduis un fragment HTML d'article de blog technique du {$from} vers le {$to}.

        Règles :
        - Traduis uniquement le texte : conserve à l'identique les balises, leurs attributs et leur ordre.
        - Ne traduis ni le code (contenu des balises code et pre), ni les URL, ni les noms de produits.
        - Conserve le ton et le registre du texte d'origine.
        - Réponds uniquement avec le HTML traduit, sans bloc de code Markdown ni commentaire.
        PROMPT;
    }
}
