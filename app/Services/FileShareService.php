<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileShare;
use App\Repositories\Interfaces\FileShareRepositoryInterface;
use App\Services\Interfaces\FileShareServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class FileShareService implements FileShareServiceInterface
{
    public function __construct(
        private FileShareRepositoryInterface $shareRepo
    ) {}

    public function share(File $file, string $sharedBy, string $sharedWith, string $permission = 'view', ?string $password = null, ?string $expiresAt = null, ?int $downloadLimit = null): FileShare
    {
        return $this->shareRepo->create([
            'file_id' => $file->id,
            'shared_by' => $sharedBy,
            'shared_with' => $sharedWith,
            'permission' => $permission,
            'password' => $password ? Hash::make($password) : null,
            'expires_at' => $expiresAt,
            'download_limit' => $downloadLimit,
        ]);
    }

    public function getSharedWithMe(string $userId): Collection
    {
        return $this->shareRepo->getSharedWithMe($userId);
    }

    public function getSharedByMe(string $userId): Collection
    {
        return $this->shareRepo->getSharedByMe($userId);
    }

    public function canAccess(File $file, string $userId): bool
    {
        if ($file->user_id === $userId) {
            return true;
        }

        if ($file->is_public) {
            return true;
        }

        return $this->shareRepo->findActiveShare($file->id, $userId) !== null;
    }

    public function canDownload(File $file, string $userId): bool
    {
        if ($file->user_id === $userId) {
            return true;
        }

        $share = $this->shareRepo->findActiveShare($file->id, $userId);

        return $share && $share->permission === 'download';
    }

    public function removeShare(string $shareId): bool
    {
        return $this->shareRepo->delete($shareId);
    }
}
