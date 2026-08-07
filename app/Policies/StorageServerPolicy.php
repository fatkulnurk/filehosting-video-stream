<?php

namespace App\Policies;

use App\Models\StorageServer;
use App\Models\User;

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
