<?php

declare(strict_types=1);

namespace App\Events\Storage;

use App\Models\Users\User;
use Illuminate\Foundation\Events\Dispatchable;

class StorageExceeded
{
    use Dispatchable;

    public function __construct(public User $user, public int $usage, public int $limit) {}
}
