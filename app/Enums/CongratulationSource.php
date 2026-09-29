<?php

namespace App\Enums;

/** D'où vient une félicitation : la carte « Distinction » de la page À propos, ou une surprise d'Armi. */
enum CongratulationSource: string
{
    case About = 'about';
    case Surprise = 'surprise';
}
