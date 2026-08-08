<?php

declare(strict_types=1);

namespace App\Policies\Storage;

use App\Models\Storage\StorageServer;
use App\Models\Users\User;

class StorageServerPolicy
{
    public function view(User $user, StorageServer $server): bool
    {
        return $user->isAdmin() || $server->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('storage.create');
    }

    public function update(User $user, StorageServer $server): bool
    {
        return $user->isAdmin() || $server->user_id === $user->id;
    }

    public function delete(User $user, StorageServer $server): bool
    {
        return $user->isAdmin() || $server->user_id === $user->id;
    }
}
