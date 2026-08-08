<?php

declare(strict_types=1);

namespace App\Services\Directories;

use App\Contracts\Repositories\Directories\DirectoryRepositoryInterface;
use App\Contracts\Services\Directories\DirectoryServiceInterface;
use App\Models\Files\Directory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DirectoryService implements DirectoryServiceInterface
{
    public function __construct(
        private DirectoryRepositoryInterface $directoryRepository
    ) {}

    public function create(string $name, ?string $parentId, string $userId): Directory
    {
        $parent = $parentId
            ? $this->directoryRepository->findById($parentId)
            : null;

        if ($parent && $parent->user_id !== $userId) {
            abort(403, 'Parent directory not found');
        }

        $slug = Str::slug($name);
        $baseSlug = $slug;
        $counter = 1;

        while ($this->slugExists($userId, $parentId, $slug)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $path = $parent ? "{$parent->path}/{$slug}" : "/{$slug}";

        Cache::forget("directory_tree:{$userId}");

        return $this->directoryRepository->create([
            'user_id' => $userId,
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'path' => $path,
        ]);
    }

    private function slugExists(string $userId, ?string $parentId, string $slug): bool
    {
        return Directory::where('user_id', $userId)
            ->where('parent_id', $parentId)
            ->where('slug', $slug)
            ->exists();
    }

    public function delete(Directory $directory): bool
    {
        $this->deleteRecursive($directory);

        Cache::forget("directory_tree:{$directory->user_id}");

        return true;
    }

    private function deleteRecursive(Directory $directory): void
    {
        foreach ($directory->children as $child) {
            $this->deleteRecursive($child);
        }

        foreach ($directory->files as $file) {
            $file->delete();
        }

        $directory->delete();
    }

    public function update(Directory $directory, string $name): Directory
    {
        $oldPath = $directory->path;
        $slug = Str::slug($name);

        $updated = $this->directoryRepository->update($directory->id, [
            'name' => $name,
            'slug' => $slug,
        ]);

        // Update path dan child paths
        $newPath = $directory->parent
            ? "{$directory->parent->path}/{$slug}"
            : "/{$slug}";

        $this->directoryRepository->update($directory->id, ['path' => $newPath]);

        $this->updateDescendantPaths($updated->id, $oldPath, $newPath);

        Cache::forget("directory_tree:{$directory->user_id}");

        return $updated->fresh();
    }

    private function updateDescendantPaths(string $directoryId, string $oldPrefix, string $newPrefix): void
    {
        $children = Directory::where('parent_id', $directoryId)->get();

        foreach ($children as $child) {
            $childPath = str_replace($oldPrefix, $newPrefix, $child->path);
            $this->directoryRepository->update($child->id, ['path' => $childPath]);
            $this->updateDescendantPaths($child->id, $oldPrefix, $newPrefix);
        }
    }

    public function move(Directory $directory, ?string $newParentId): Directory
    {
        if ($newParentId === $directory->id) {
            abort(422, 'Cannot move directory into itself');
        }

        $newParent = $newParentId ? $this->directoryRepository->findById($newParentId) : null;

        if ($newParent && $newParent->user_id !== $directory->user_id) {
            abort(403, 'Invalid parent');
        }

        // Cek circular
        $ancestorIds = $this->getSubdirectoryIds($directory);

        if ($newParentId && in_array($newParentId, $ancestorIds)) {
            abort(422, 'Cannot move directory into its own subdirectory');
        }

        $oldPrefix = $directory->path;
        $newPath = $newParent ? "{$newParent->path}/{$directory->slug}" : "/{$directory->slug}";

        $updated = $this->directoryRepository->update($directory->id, [
            'parent_id' => $newParentId,
            'path' => $newPath,
        ]);

        $this->updateDescendantPaths($directory->id, $oldPrefix, $newPath);

        Cache::forget("directory_tree:{$directory->user_id}");

        return $updated;
    }

    public function getTree(string $userId): SupportCollection
    {
        return $this->directoryRepository->getTree($userId);
    }

    public function getPath(string $id, string $userId): SupportCollection
    {
        return $this->directoryRepository->getPath($id, $userId);
    }

    public function getBreadcrumbs(string $id): SupportCollection
    {
        return $this->directoryRepository->getBreadcrumbs($id);
    }

    public function getSubdirectoryIds(Directory $directory): array
    {
        $ids = [];

        foreach ($directory->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getSubdirectoryIds($child));
        }

        return $ids;
    }

    public function getChildDirectories(string $userId, ?string $parentId): Collection
    {
        return $this->directoryRepository->getUserDirectories($userId, $parentId);
    }

    public function getOwnedDirectory(string $userId, string $id): Directory
    {
        $directory = $this->directoryRepository->findById($id);

        if (! $directory || $directory->user_id !== $userId) {
            abort(404);
        }

        return $directory;
    }

    public function getDirectoryForAdmin(string $id): Directory
    {
        $directory = $this->directoryRepository->findWithTrashed($id);

        if (! $directory) {
            abort(404);
        }

        return $directory;
    }

    public function forceDelete(Directory $directory): bool
    {
        return $this->directoryRepository->forceDelete($directory->id);
    }

    public function paginateAllForAdmin(array $params): LengthAwarePaginator
    {
        return $this->directoryRepository->paginateAdmin($params);
    }

    public function countAllWithTrashed(string $userId): int
    {
        return $this->directoryRepository->countAllWithTrashed($userId);
    }

    public function getLatestForUser(string $userId, int $limit = 10): Collection
    {
        return $this->directoryRepository->getLatestForUser($userId, $limit);
    }
}
