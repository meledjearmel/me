<?php

namespace App\Enums;

/** Visibilité d'un contenu sur le site public, sur le CV et dans l'API publique. */
enum PublicationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
