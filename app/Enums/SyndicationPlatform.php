<?php

namespace App\Enums;

/** Les plateformes où un article est republié par API, avec un lien canonique vers le site. */
enum SyndicationPlatform: string
{
    case DevTo = 'devto';
    case Hashnode = 'hashnode';

    public function label(): string
    {
        return match ($this) {
            self::DevTo => 'dev.to',
            self::Hashnode => 'Hashnode',
        };
    }
}
