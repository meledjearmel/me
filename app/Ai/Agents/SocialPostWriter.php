<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Rédige, pour un article du blog d'un développeur, le texte à partager sur LinkedIn, X,
 * Facebook et en statut WhatsApp, chacun au ton et à la longueur du réseau.
 */
#[MaxTokens(1800)]
#[Temperature(0.6)]
class SocialPostWriter implements Agent
{
    use Promptable;

    public function __construct(private readonly string $locale) {}

    public function timeout(): int
    {
        return (int) config('ai.text_assist.timeout');
    }

    public function instructions(): Stringable|string
    {
        $language = $this->locale === 'en' ? 'anglais' : 'français';

        return <<<PROMPT
        Tu écris, en {$language}, les textes qui annoncent un article du blog d'un développeur web sur
        ses réseaux. Première personne, ton sincère et concret : l'idée clé de l'article ou ce que le
        lecteur y apprendra, sans superlatifs ni promesses creuses, sans Markdown. N'écris pas l'adresse
        de l'article : elle est ajoutée automatiquement à la fin.

        - "linkedin" : 3 à 6 phrases courtes en 2 ou 3 paragraphes (ligne vide entre eux), une accroche
          en première ligne, puis 3 à 5 hashtags pertinents sur la dernière ligne.
        - "x" : 200 caractères au plus, une seule idée forte, 1 ou 2 hashtags.
        - "facebook" : 2 à 4 phrases, plus conversationnel, sans hashtag ou un seul.
        - "whatsapp" : statut très court, 1 ou 2 phrases (120 caractères au plus), un emoji au plus.

        Réponds uniquement avec un objet JSON valide, sans bloc de code ni commentaire :
        {"linkedin": "…", "x": "…", "facebook": "…", "whatsapp": "…"}
        PROMPT;
    }
}
