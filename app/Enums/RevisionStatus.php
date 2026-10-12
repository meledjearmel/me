<?php

namespace App\Enums;

/** Révision d'un contenu publié proposée par l'IA : en attente, appliquée ou rejetée. */
enum RevisionStatus: string
{
    case Pending = 'pending';

    case Applied = 'applied';

    case Rejected = 'rejected';
}
