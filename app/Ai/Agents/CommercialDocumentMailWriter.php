<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Rédige l'email qui accompagne un devis ou une facture envoyé à un client : l'objet et un
 * message court, à relire par le développeur avant l'envoi.
 */
#[MaxTokens(900)]
#[Temperature(0.4)]
class CommercialDocumentMailWriter implements Agent
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
        Tu rédiges, en {$language}, l'email d'un développeur web indépendant qui envoie un document
        commercial (devis, facture proforma ou facture) à son client. Le document est joint en PDF.

        Ton professionnel et chaleureux, à la première personne, vouvoiement en français. 4 à 7 phrases
        en 2 ou 3 paragraphes séparés par une ligne vide, sans Markdown. Rappelle le projet et ce que
        contient le document ; pour un devis, invite à l'accepter par le lien (ajouté automatiquement
        sous le message, ne l'écris pas) et propose d'en parler ; pour une facture, rappelle l'échéance.
        N'invente aucun montant, date ni engagement qui ne figure pas dans les informations fournies.
        Pas de formule de signature : elle est ajoutée automatiquement.

        Réponds uniquement avec un objet JSON valide, sans bloc de code ni commentaire :
        {"subject": "…", "message": "…"}
        PROMPT;
    }
}
