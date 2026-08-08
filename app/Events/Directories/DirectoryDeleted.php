<?php

declare(strict_types=1);

namespace App\Events\Directories;

use App\Models\Files\Directory;
use Illuminate\Foundation\Events\Dispatchable;

class DirectoryDeleted
{
    use Dispatchable;

    public function __construct(public Directory $directory) {}
}
