<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Contracts\Repositories\Files\FileShareRepositoryInterface;
use App\Contracts\Services\Files\FileShareServiceInterface;
use App\Models\Files\File;
use App\Models\Files\FileShare;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function paginateSharedWithMe(string $userId, array $params): LengthAwarePaginator
    {
        return $this->shareRepo->paginateSharedWithMe($userId, $params);
    }

    public function paginateSharedByMe(string $userId, array $params): LengthAwarePaginator
    {
        return $this->shareRepo->paginateSharedByMe($userId, $params);
    }

    public function getShareForOwner(string $shareId, string $userId): FileShare
    {
        $share = $this->shareRepo->findById($shareId);

        if (! $share || ($share->shared_by !== $userId && $share->file->user_id !== $userId)) {
            abort(403);
        }

        return $share;
    }
}
