<?php

namespace App\Enums;

/** Comment un article part sur une plateforme de republication. */
enum SyndicationMode: string
{
    /** Jamais. */
    case Off = 'off';
    /** Avec le bouton de la fiche de l'article. */
    case Manual = 'manual';
    /** Tout seul, dès qu'un article est publié (après l'activation du mode). */
    case Auto = 'auto';
}
