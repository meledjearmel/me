<?php

namespace App\Enums;

/** Réaction d'un lecteur à un article, affichée en emoji sous le contenu. */
enum PostReactionType: string
{
    case Like = 'like';

    case Love = 'love';

    case Fire = 'fire';

    case Idea = 'idea';

    case Think = 'think';
}
