<?php

namespace App\Enums;

enum ProjectStatus: string
{
    /** Proposé par l'IA (serveur MCP), en français, à relire et traduire avant publication. */
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
