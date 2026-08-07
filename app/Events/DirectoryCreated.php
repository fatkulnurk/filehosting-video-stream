<?php

namespace App\Events;

use App\Models\Directory;
use Illuminate\Foundation\Events\Dispatchable;

class DirectoryCreated
{
    use Dispatchable;

    public function __construct(public Directory $directory) {}
}
