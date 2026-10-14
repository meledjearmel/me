<?php

namespace App\Enums;

/** L'issue d'une demande de collaboration : signée (avec son montant) ou perdue. */
enum EngagementOutcome: string
{
    case Won = 'won';
    case Lost = 'lost';
}
