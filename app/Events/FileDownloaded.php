<?php

namespace App\Events;

use App\Models\File;
use Illuminate\Foundation\Events\Dispatchable;

class FileDownloaded
{
    use Dispatchable;

    public function __construct(public File $file) {}
}
