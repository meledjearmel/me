<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Published = 'published';
    case Archived = 'archived';
}
