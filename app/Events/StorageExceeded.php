<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class StorageExceeded
{
    use Dispatchable;

    public function __construct(public User $user, public int $usage, public int $limit) {}
}
