<?php

namespace App\Events;

use App\Models\File;
use Illuminate\Foundation\Events\Dispatchable;

class FileDeleted
{
    use Dispatchable;

    public function __construct(public File $file) {}
}
