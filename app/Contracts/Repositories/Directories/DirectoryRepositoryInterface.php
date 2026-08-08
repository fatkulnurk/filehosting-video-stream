<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Directories;

use App\Models\Files\Directory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

interface DirectoryRepositoryInterface
{
    public function create(array $data): Directory;

    public function findById(string $id): ?Directory;

    public function findWithTrashed(string $id): ?Directory;

    public function getUserDirectories(string $userId, ?string $parentId = null): Collection;

    public function getTree(string $userId): Collection;

    public function getPath(string $id, string $userId): SupportCollection;

    public function update(string $id, array $data): Directory;

    public function delete(string $id): bool;

    public function forceDelete(string $id): bool;

    public function getBreadcrumbs(string $id): SupportCollection;

    public function countAll(string $userId): int;

    public function countAllWithTrashed(string $userId): int;

    public function getLatestForUser(string $userId, int $limit = 10): Collection;

    public function paginateAdmin(array $params): LengthAwarePaginator;
}
