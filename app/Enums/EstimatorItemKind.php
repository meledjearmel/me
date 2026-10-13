<?php

namespace App\Enums;

/** Ce qu'est un élément de l'estimateur : le type de projet (un seul, la base) ou une option qui s'y ajoute. */
enum EstimatorItemKind: string
{
    case ProjectType = 'project_type';
    case Feature = 'feature';
}
