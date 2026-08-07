<?php

namespace App\Services\Interfaces;

use App\Models\File;
use App\Models\FileShare;
use Illuminate\Database\Eloquent\Collection;

interface FileShareServiceInterface
{
    public function share(File $file, string $sharedBy, string $sharedWith, string $permission = 'view', ?string $password = null, ?string $expiresAt = null, ?int $downloadLimit = null): FileShare;

    public function getSharedWithMe(string $userId): Collection;

    public function getSharedByMe(string $userId): Collection;

    public function canAccess(File $file, string $userId): bool;

    public function canDownload(File $file, string $userId): bool;

    public function removeShare(string $shareId): bool;
}
