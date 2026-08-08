<?php

declare(strict_types=1);

namespace App\Policies\Files;

use App\Contracts\Services\Directories\DirectoryShareServiceInterface;
use App\Contracts\Services\Files\FileShareServiceInterface;
use App\Models\Files\File;
use App\Models\Users\User;

class FilePolicy
{
    public function view(User $user, File $file): bool
    {
        if ($user->isAdmin() || $file->user_id === $user->id) {
            return true;
        }

        return app(FileShareServiceInterface::class)->canAccess($file, $user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('file.upload');
    }

    public function update(User $user, File $file): bool
    {
        if ($file->user_id === $user->id) {
            return true;
        }

        return app(DirectoryShareServiceInterface::class)
            ->canUpload($file->directory, $user->id);
    }

    public function delete(User $user, File $file): bool
    {
        if ($user->isAdmin() || $file->user_id === $user->id) {
            return true;
        }

        return app(DirectoryShareServiceInterface::class)
            ->canManage($file->directory, $user->id);
    }

    public function manage(User $user, File $file): bool
    {
        return $user->isAdmin() || $file->user_id === $user->id;
    }

    public function download(User $user, File $file): bool
    {
        if ($user->isAdmin() || $file->user_id === $user->id) {
            return true;
        }

        return app(FileShareServiceInterface::class)->canDownload($file, $user->id);
    }
}
