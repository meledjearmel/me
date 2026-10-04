<?php

namespace App\Enums;

/** Modération d'un commentaire du blog : seuls les commentaires approuvés sont publics. */
enum CommentStatus: string
{
    case Pending = 'pending';

    case Approved = 'approved';

    case Rejected = 'rejected';
}
