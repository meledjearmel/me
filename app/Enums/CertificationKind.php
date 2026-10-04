<?php

namespace App\Enums;

/** Nature d'une entrée de la page Certifications. */
enum CertificationKind: string
{
    /** Certification obtenue après un examen (souvent vérifiable en ligne). */
    case Certification = 'certification';
    /** Formation courte suivie (cours en ligne, bootcamp, atelier). */
    case Course = 'course';
}
