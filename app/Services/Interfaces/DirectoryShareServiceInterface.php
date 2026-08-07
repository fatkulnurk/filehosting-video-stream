<?php

namespace App\Services\Interfaces;

use App\Models\Directory;
use App\Models\DirectoryShare;
use Illuminate\Database\Eloquent\Collection;

interface DirectoryShareServiceInterface
{
    public function share(Directory $directory, string $sharedBy, string $sharedWith, string $permission = 'view', bool $recursive = true): DirectoryShare;

    public function getSharedWithMe(string $userId): Collection;

    public function canAccess(Directory $directory, string $userId): bool;

    public function canUpload(Directory $directory, string $userId): bool;

    public function canManage(Directory $directory, string $userId): bool;

    public function getAccessibleDirectories(string $userId): Collection;

    public function removeShare(string $shareId): bool;
}
