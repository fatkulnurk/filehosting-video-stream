<?php

namespace App\Policies;

use App\Models\Directory;
use App\Models\User;
use App\Services\Interfaces\DirectoryShareServiceInterface;

class DirectoryPolicy
{
    public function view(User $user, Directory $directory): bool
    {
        if ($user->isAdmin() || $directory->user_id === $user->id) {
            return true;
        }

        return app(DirectoryShareServiceInterface::class)->canAccess($directory, $user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('directory.create');
    }

    public function update(User $user, Directory $directory): bool
    {
        if ($directory->user_id === $user->id) {
            return true;
        }

        return app(DirectoryShareServiceInterface::class)->canUpload($directory, $user->id);
    }

    public function delete(User $user, Directory $directory): bool
    {
        if ($user->isAdmin() || $directory->user_id === $user->id) {
            return true;
        }

        return app(DirectoryShareServiceInterface::class)->canManage($directory, $user->id);
    }

    public function manage(User $user, Directory $directory): bool
    {
        return $user->isAdmin() || $directory->user_id === $user->id
            || app(DirectoryShareServiceInterface::class)->canManage($directory, $user->id);
    }
}
