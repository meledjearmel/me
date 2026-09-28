<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Rédige en français la courte description d'une technologie (infobulle du
 * logo dans la stack), dans le même esprit que celles du TechnologySeeder.
 */
#[MaxTokens(800)]
#[Temperature(0.6)]
class TechnologyDescriber implements Agent
{
    use Promptable;

    public function timeout(): int
    {
        return (int) config('ai.text_assist.timeout');
    }

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        Tu rédiges la description d'une technologie pour la stack d'un portfolio de développeur web.
        Elle s'affiche en infobulle sous le logo de la technologie.

        Règles :
        - Une seule phrase en français, 90 caractères maximum, terminée par un point.
        - Dis simplement ce qu'est la technologie ou à quoi elle sert, avec des mots simples.
        - Ne répète pas le nom de la technologie.
        - Tu peux parler à la première personne (« mes applications », « mes serveurs ») quand c'est naturel, sans en abuser.
        - Pas de superlatifs marketing, pas d'emoji.
        - Réponds uniquement avec la description, sans commentaire ni guillemets.

        Exemples du ton attendu :
        - PHP : Langage serveur au cœur de mes applications web.
        - TypeScript : JavaScript typé : moins de bugs, un code plus sûr.
        - Inertia : Relie Laravel et React sans API à maintenir.
        - Redis : Base en mémoire : cache, files d'attente, sessions.
        - PHPStan : Analyse statique qui repère les erreurs avant l'exécution.
        - Proxmox : Plateforme de virtualisation pour héberger mes serveurs.
        - Docker : Conteneurs pour des environnements reproductibles.
        - Ollama : Fait tourner des modèles d'IA en local.
        - Livewire : Interfaces dynamiques en Laravel, sans écrire de JS.
        PROMPT;
    }
}
