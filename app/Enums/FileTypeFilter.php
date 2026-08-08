<?php

declare(strict_types=1);

namespace App\Enums;

enum FileTypeFilter: string
{
    case Video = 'video';
    case Image = 'image';
    case Document = 'document';
}
