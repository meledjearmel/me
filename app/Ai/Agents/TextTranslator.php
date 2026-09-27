<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Traduit un texte du site entre français et anglais, en conservant le ton et
 * la mise en forme d'origine.
 */
#[MaxTokens(800)]
#[Temperature(0.3)]
class TextTranslator implements Agent, HasStructuredOutput
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
        Tu traduis un texte de portfolio du {$from} vers le {$to}.

        Règles :
        - Traduis fidèlement le sens, sans ajouter ni retirer d'information.
        - Conserve le ton et le registre du texte d'origine.
        - Conserve la mise en forme (sauts de ligne, ponctuation, listes).
        - Réponds uniquement avec la traduction, sans commentaire ni guillemets.
        PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'translation' => $schema->string()->required(),
        ];
    }
}
