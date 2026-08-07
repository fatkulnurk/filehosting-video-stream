<?php

namespace App\Events;

use App\Models\Directory;
use Illuminate\Foundation\Events\Dispatchable;

class DirectoryDeleted
{
    use Dispatchable;

    public function __construct(public Directory $directory) {}
}
