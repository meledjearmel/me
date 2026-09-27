<?php

namespace App\Enums;

enum TextTone: string
{
    case Formal = 'formal';
    case Friendly = 'friendly';
    case Concise = 'concise';
    case Enthusiastic = 'enthusiastic';

    /**
     * Consigne de ton insérée dans le prompt de l'agent d'amélioration.
     */
    public function instruction(): string
    {
        return match ($this) {
            self::Formal => 'Adopte un ton plus formel et professionnel.',
            self::Friendly => 'Adopte un ton plus chaleureux et accessible.',
            self::Concise => 'Rends le texte plus concis, sans perdre le sens.',
            self::Enthusiastic => 'Adopte un ton plus enthousiaste et dynamique.',
        };
    }
}
