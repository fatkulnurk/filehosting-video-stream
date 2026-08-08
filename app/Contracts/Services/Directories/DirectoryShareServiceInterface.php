<?php

declare(strict_types=1);

namespace App\Contracts\Services\Directories;

use App\Models\Files\Directory;
use App\Models\Files\DirectoryShare;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DirectoryShareServiceInterface
{
    public function share(Directory $directory, string $sharedBy, string $sharedWith, string $permission = 'view', bool $recursive = true): DirectoryShare;

    public function getSharedWithMe(string $userId): Collection;

    public function canAccess(Directory $directory, string $userId): bool;

    public function canUpload(Directory $directory, string $userId): bool;

    public function canManage(Directory $directory, string $userId): bool;

    public function getAccessibleDirectories(string $userId): Collection;

    public function removeShare(string $shareId): bool;

    public function findUserByEmail(string $email): ?User;

    public function paginateSharedWithMe(string $userId, array $params): LengthAwarePaginator;

    public function getShareForOwner(string $shareId, string $userId): DirectoryShare;
}
