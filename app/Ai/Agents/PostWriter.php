<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Assistant de rédaction des articles du blog : réécrit la sélection ou rédige un passage
 * à insérer, en HTML compatible avec l'éditeur Tiptap de l'admin.
 */
#[MaxTokens(2500)]
#[Temperature(0.6)]
class PostWriter implements Agent
{
    use Promptable;

    public function __construct(
        private readonly string $locale,
        private readonly ?string $articleTitle = null,
        private readonly ?string $articleExcerpt = null,
        private readonly bool $hasSelection = false,
    ) {}

    public function timeout(): int
    {
        return (int) config('ai.text_assist.timeout');
    }

    public function instructions(): Stringable|string
    {
        $language = $this->locale === 'en' ? 'anglais' : 'français';
        $task = $this->hasSelection
            ? 'On te donne un passage sélectionné (entre <selection> et </selection>) et une consigne : renvoie uniquement le passage réécrit selon la consigne, qui le remplacera.'
            : 'On te donne une consigne, parfois avec la fin du texte qui précède le curseur (entre <contexte> et </contexte>) : rédige uniquement le passage à insérer à cet endroit.';
        $title = filled($this->articleTitle) ? "Titre de l'article : {$this->articleTitle}" : '';
        $excerpt = filled($this->articleExcerpt) ? "Résumé de l'article : {$this->articleExcerpt}" : '';

        return <<<PROMPT
        Tu aides à rédiger un article de blog technique en {$language}, publié sur le portfolio d'un développeur.
        {$title}
        {$excerpt}

        {$task}

        Règles :
        - Écris en {$language}, sauf si la consigne demande une autre langue.
        - Réponds en HTML simple, sans balise <html> ni <body>, et sans bloc de code Markdown autour.
        - Balises autorisées : p, h2, h3, h4, strong, em, u, s, mark, code, pre, blockquote, ul, ol, li, a, table, tr, th, td, hr, sub, sup.
        - Pas d'attribut style ni class, pas de script.
        - Ne répète ni la consigne ni le contexte, et n'ajoute aucun commentaire.
        PROMPT;
    }
}
