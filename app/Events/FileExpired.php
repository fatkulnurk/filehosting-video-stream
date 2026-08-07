<?php

namespace App\Events;

use App\Models\File;
use Illuminate\Foundation\Events\Dispatchable;

class FileExpired
{
    use Dispatchable;

    public function __construct(public File $file) {}
}
