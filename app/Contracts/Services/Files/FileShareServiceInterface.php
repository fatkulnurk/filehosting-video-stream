<?php

declare(strict_types=1);

namespace App\Contracts\Services\Files;

use App\Models\Files\File;
use App\Models\Files\FileShare;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface FileShareServiceInterface
{
    public function share(File $file, string $sharedBy, string $sharedWith, string $permission = 'view', ?string $password = null, ?string $expiresAt = null, ?int $downloadLimit = null): FileShare;

    public function getSharedWithMe(string $userId): Collection;

    public function getSharedByMe(string $userId): Collection;

    public function canAccess(File $file, string $userId): bool;

    public function canDownload(File $file, string $userId): bool;

    public function removeShare(string $shareId): bool;

    public function findUserByEmail(string $email): ?User;

    public function paginateSharedWithMe(string $userId, array $params): LengthAwarePaginator;

    public function paginateSharedByMe(string $userId, array $params): LengthAwarePaginator;

    public function getShareForOwner(string $shareId, string $userId): FileShare;
}
