<?php

namespace App\Enums;

/** Moyen utilisé par un lecteur pour partager un article. */
enum PostShareNetwork: string
{
    case Linkedin = 'linkedin';

    case X = 'x';

    case Whatsapp = 'whatsapp';

    case Facebook = 'facebook';

    case Email = 'email';

    /** Lien copié dans le presse-papiers. */
    case Copy = 'copy';

    /** Feuille de partage du système (mobile). */
    case Native = 'native';
}
