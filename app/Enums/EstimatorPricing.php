<?php

namespace App\Enums;

/** Comment une option s'ajoute au projet : des heures fixes, un pourcentage des heures du projet, ou des heures par mois facturées à chaque période (hors total du projet). */
enum EstimatorPricing: string
{
    case Hours = 'hours';
    case Percent = 'percent';
    case Recurring = 'recurring';
}
