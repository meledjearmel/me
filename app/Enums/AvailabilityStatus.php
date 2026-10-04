<?php

namespace App\Enums;

/** Ma disponibilité affichée sur le site (badge de l'en-tête, fenêtre de contact). */
enum AvailabilityStatus: string
{
    case Available = 'available';
    /** Disponible à partir d'une date (`available_from`) ; disponible une fois la date passée. */
    case From = 'from';
    case Unavailable = 'unavailable';
}
