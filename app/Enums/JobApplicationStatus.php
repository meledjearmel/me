<?php

namespace App\Enums;

/** Où en est une page de candidature. */
enum JobApplicationStatus: string
{
    /** En préparation : le lien ne répond pas encore. */
    case Draft = 'draft';
    /** En ligne : la page s'ouvre avec son lien. */
    case Active = 'active';
    /** Archivée : le lien est coupé, l'historique reste dans l'admin. */
    case Archived = 'archived';
}
