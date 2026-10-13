<?php

namespace App\Enums;

/** Comment une option s'ajoute au projet : des heures fixes, ou un pourcentage des heures du projet. */
enum EstimatorPricing: string
{
    case Hours = 'hours';
    case Percent = 'percent';
}
