<?php

declare(strict_types=1);

namespace App\Enums;

enum DirectoryStatusFilter: string
{
    case Active = 'active';
    case Trashed = 'trashed';
}
