<?php

namespace App\Services;

use App\Models\Directory;
use App\Repositories\Interfaces\DirectoryRepositoryInterface;
use App\Services\Interfaces\DirectoryServiceInterface;
use Illuminate\Support\Collection;
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
        return \App\Models\Directory::where('user_id', $userId)
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
        $children = \App\Models\Directory::where('parent_id', $directoryId)->get();

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

    public function getTree(string $userId): Collection
    {
        return $this->directoryRepository->getTree($userId);
    }

    public function getPath(string $id, string $userId): Collection
    {
        return $this->directoryRepository->getPath($id, $userId);
    }

    public function getBreadcrumbs(string $id): Collection
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
}
