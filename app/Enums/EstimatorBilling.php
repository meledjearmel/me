<?php

namespace App\Enums;

/** Comment un type de projet est présenté au visiteur : un forfait estimé, ou des heures au taux horaire. */
enum EstimatorBilling: string
{
    case Fixed = 'fixed';
    case Hourly = 'hourly';
}
