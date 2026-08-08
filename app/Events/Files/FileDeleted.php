<?php

declare(strict_types=1);

namespace App\Events\Files;

use App\Models\Files\File;
use Illuminate\Foundation\Events\Dispatchable;

class FileDeleted
{
    use Dispatchable;

    public function __construct(public File $file) {}
}
