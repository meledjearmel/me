<?php

namespace App\Enums;

/** Rubriques de la page « Uses », dans leur ordre d'affichage. */
enum UsesCategory: string
{
    case Hardware = 'hardware';
    case Development = 'development';
    case Apps = 'apps';
    case Services = 'services';
}
