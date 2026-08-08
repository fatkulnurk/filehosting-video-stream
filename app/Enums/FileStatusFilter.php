<?php

declare(strict_types=1);

namespace App\Enums;

enum FileStatusFilter: string
{
    case Active = 'active';
    case Public = 'public';
    case Private = 'private';
    case Trashed = 'trashed';
}
