<?php

namespace App\Ai\Agents;

use App\Enums\TextTone;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Améliore la formulation d'un texte du site dans sa langue d'origine, sans le traduire.
 */
#[MaxTokens(800)]
#[Temperature(0.5)]
class TextImprover implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private readonly string $locale,
        private readonly ?TextTone $tone = null,
        private readonly ?string $customInstructions = null,
    ) {}

    public function timeout(): int
    {
        return (int) config('ai.text_assist.timeout');
    }

    public function instructions(): Stringable|string
    {
        $language = $this->locale === 'en' ? 'anglais' : 'français';
        $tone = $this->tone?->instruction() ?? '';
        $custom = filled($this->customInstructions) ? "Consigne supplémentaire : {$this->customInstructions}" : '';

        return <<<PROMPT
        Tu améliores la formulation d'un texte de portfolio écrit en {$language}, sans changer sa langue.

        Règles :
        - Conserve le sens et les informations factuelles du texte d'origine.
        - Corrige les fautes et améliore la fluidité.
        {$tone}
        {$custom}
        - Réponds uniquement avec le texte amélioré, sans commentaire ni guillemets.
        PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()->required(),
        ];
    }
}
