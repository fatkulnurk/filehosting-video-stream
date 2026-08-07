<?php

namespace App\Repositories;

use App\Models\Directory;
use App\Repositories\Interfaces\DirectoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class DirectoryRepository implements DirectoryRepositoryInterface
{
    public function create(array $data): Directory
    {
        return Directory::create($data);
    }

    public function findById(string $id): ?Directory
    {
        return Directory::find($id);
    }

    public function getUserDirectories(string $userId, ?string $parentId = null): Collection
    {
        return Directory::where('user_id', $userId)
            ->when($parentId, fn ($q) => $q->where('parent_id', $parentId))
            ->when($parentId === null, fn ($q) => $q->whereNull('parent_id'))
            ->withCount('files')
            ->orderBy('name')
            ->get();
    }

    public function getTree(string $userId): Collection
    {
        $allDirectories = Directory::where('user_id', $userId)
            ->orderBy('path')
            ->get();

        return $this->buildTree($allDirectories);
    }

    private function buildTree(Collection $directories): Collection
    {
        $grouped = $directories->groupBy('parent_id');
        $root = $grouped->get(null, new Collection);

        return $root->map(function (Directory $directory) use ($grouped) {
            return $this->buildNode($directory, $grouped);
        })->values();
    }

    private function buildNode(Directory $directory, $grouped): Directory
    {
        $children = $grouped->get($directory->id, new Collection);

        $directory->setRelation('children', $children->map(function (Directory $child) use ($grouped) {
            return $this->buildNode($child, $grouped);
        }));

        return $directory;
    }

    public function getPath(string $id, string $userId): SupportCollection
    {
        $directory = Directory::where('id', $id)->where('user_id', $userId)->first();

        if (! $directory) {
            return collect();
        }

        return $this->getBreadcrumbs($directory->id);
    }

    public function update(string $id, array $data): Directory
    {
        $directory = Directory::findOrFail($id);
        $directory->update($data);

        return $directory->fresh();
    }

    public function delete(string $id): bool
    {
        return Directory::where('id', $id)->delete() > 0;
    }

    public function forceDelete(string $id): bool
    {
        return Directory::where('id', $id)->withTrashed()->forceDelete() > 0;
    }

    public function getBreadcrumbs(string $id): SupportCollection
    {
        $breadcrumbs = collect();
        $directory = Directory::find($id);

        while ($directory) {
            $breadcrumbs->prepend($directory);
            $directory = $directory->parent;
        }

        return $breadcrumbs;
    }

    public function countAll(string $userId): int
    {
        return Directory::where('user_id', $userId)->count();
    }
}
