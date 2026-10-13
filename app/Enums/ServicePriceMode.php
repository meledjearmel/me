<?php

namespace App\Enums;

/** D'où vient le prix « à partir de » d'une offre. */
enum ServicePriceMode: string
{
    /** Calculé par l'estimateur, depuis le type de projet lié. */
    case Estimator = 'estimator';
    /** Saisi à la main, par devise. */
    case Manual = 'manual';
    /** Pas de prix affiché : « sur devis ». */
    case Quote = 'quote';
}
