<?php

declare(strict_types=1);

namespace App\Repositories\Directories;

use App\Contracts\Repositories\Directories\DirectoryRepositoryInterface;
use App\Enums\DirectoryStatusFilter;
use App\Models\Files\Directory;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function findWithTrashed(string $id): ?Directory
    {
        return Directory::withTrashed()->find($id);
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

    public function countAllWithTrashed(string $userId): int
    {
        return Directory::withTrashed()->where('user_id', $userId)->count();
    }

    public function getLatestForUser(string $userId, int $limit = 10): Collection
    {
        return Directory::withCount('files')
            ->withTrashed()
            ->where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function paginateAdmin(array $params): LengthAwarePaginator
    {
        $sortable = [
            'name' => 'name',
            'user' => \DB::raw('(SELECT u.name FROM users u WHERE u.id = directories.user_id)'),
            'path' => 'path',
            'files' => 'files_count',
            'created_at' => 'created_at',
        ];

        $sorts = $params['sort'] ?? [];
        $filters = $params['filters'] ?? [];

        $query = Directory::query()
            ->with(['user', 'parent'])
            ->withCount('files')
            ->withTrashed();

        $userId = $filters['user_id'] ?? null;
        if ($userId && $userId !== '') {
            $query->where('user_id', $userId);
        }

        $status = $filters['status'] ?? null;
        if ($status && $status !== 'all') {
            match (DirectoryStatusFilter::tryFrom($status)) {
                DirectoryStatusFilter::Active => $query->whereNull('deleted_at'),
                DirectoryStatusFilter::Trashed => $query->onlyTrashed(),
                default => null,
            };
        }

        if (($params['search'] ?? '') !== '') {
            $query->where('name', 'LIKE', '%'.$params['search'].'%');
        }

        if ($sorts) {
            foreach ($sorts as $field => $direction) {
                if (! isset($sortable[$field])) {
                    continue;
                }

                $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
                $target = $sortable[$field];

                if ($target instanceof Expression) {
                    $query->orderByRaw($target->getValue($query->getQuery()->getGrammar()).' '.$direction);
                } else {
                    $query->orderBy($target, $direction);
                }
            }
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }
}
