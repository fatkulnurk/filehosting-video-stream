<?php

declare(strict_types=1);

namespace App\Services\Directories;

use App\Contracts\Repositories\Directories\DirectoryShareRepositoryInterface;
use App\Contracts\Services\Directories\DirectoryShareServiceInterface;
use App\Models\Files\Directory;
use App\Models\Files\DirectoryShare;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DirectoryShareService implements DirectoryShareServiceInterface
{
    public function __construct(
        private DirectoryShareRepositoryInterface $shareRepo
    ) {}

    public function share(Directory $directory, string $sharedBy, string $sharedWith, string $permission = 'view', bool $recursive = true): DirectoryShare
    {
        return $this->shareRepo->create([
            'directory_id' => $directory->id,
            'shared_by' => $sharedBy,
            'shared_with' => $sharedWith,
            'permission' => $permission,
            'recursive' => $recursive,
        ]);
    }

    public function getSharedWithMe(string $userId): Collection
    {
        return $this->shareRepo->getSharedWithMe($userId);
    }

    public function canAccess(Directory $directory, string $userId): bool
    {
        if ($directory->user_id === $userId) {
            return true;
        }

        if ($this->shareRepo->findActiveShare($directory->id, $userId)) {
            return true;
        }

        if ($directory->parent_id) {
            return $this->canAccess($directory->parent, $userId);
        }

        return false;
    }

    public function canUpload(Directory $directory, string $userId): bool
    {
        if ($directory->user_id === $userId) {
            return true;
        }

        $share = $this->shareRepo->findActiveShare($directory->id, $userId);

        if ($share && in_array($share->permission, ['upload', 'manage'])) {
            return true;
        }

        if ($directory->parent_id) {
            return $this->canUpload($directory->parent, $userId);
        }

        return false;
    }

    public function canManage(Directory $directory, string $userId): bool
    {
        if ($directory->user_id === $userId) {
            return true;
        }

        $share = $this->shareRepo->findActiveShare($directory->id, $userId);

        if ($share && $share->permission === 'manage') {
            return true;
        }

        if ($directory->parent_id) {
            return $this->canManage($directory->parent, $userId);
        }

        return false;
    }

    public function getAccessibleDirectories(string $userId): Collection
    {
        $own = Directory::where('user_id', $userId)
            ->whereNull('parent_id')
            ->get();

        $shared = DirectoryShare::where('shared_with', $userId)
            ->with('directory')
            ->get()
            ->pluck('directory')
            ->filter(fn (Directory $dir) => $dir->parent_id === null);

        return $own->concat($shared)->unique('id')->values();
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

    public function getShareForOwner(string $shareId, string $userId): DirectoryShare
    {
        $share = $this->shareRepo->findById($shareId);

        if (! $share || ($share->shared_by !== $userId && $share->directory->user_id !== $userId)) {
            abort(403);
        }

        return $share;
    }
}
