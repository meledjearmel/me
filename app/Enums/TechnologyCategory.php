<?php

namespace App\Enums;

enum TechnologyCategory: string
{
    case Langages = 'langages';
    case Frameworks = 'frameworks';
    case Donnees = 'donnees';
    case Qualite = 'qualite';
    case Securite = 'securite';
    case Infra = 'infra';
    case Ia = 'ia';
}
