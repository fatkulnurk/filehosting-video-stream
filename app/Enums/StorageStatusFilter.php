<?php

declare(strict_types=1);

namespace App\Enums;

enum StorageStatusFilter: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
