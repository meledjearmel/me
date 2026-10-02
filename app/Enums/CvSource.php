<?php

namespace App\Enums;

/** D'où vient le CV proposé au téléchargement. */
enum CvSource: string
{
    /** Le PDF importé dans l'admin pour le profil métier. */
    case Uploaded = 'uploaded';

    /** Le PDF généré à partir du contenu du site. */
    case Generated = 'generated';
}
