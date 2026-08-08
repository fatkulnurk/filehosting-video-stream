<?php

declare(strict_types=1);

namespace App\Contracts\Services\Directories;

use App\Models\Files\Directory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

interface DirectoryServiceInterface
{
    public function create(string $name, ?string $parentId, string $userId): Directory;

    public function delete(Directory $directory): bool;

    public function update(Directory $directory, string $name): Directory;

    public function move(Directory $directory, ?string $newParentId): Directory;

    public function getTree(string $userId): SupportCollection;

    public function getPath(string $id, string $userId): SupportCollection;

    public function getBreadcrumbs(string $id): SupportCollection;

    public function getSubdirectoryIds(Directory $directory): array;

    public function getChildDirectories(string $userId, ?string $parentId): Collection;

    public function getOwnedDirectory(string $userId, string $id): Directory;

    public function getDirectoryForAdmin(string $id): Directory;

    public function forceDelete(Directory $directory): bool;

    public function paginateAllForAdmin(array $params): LengthAwarePaginator;

    public function countAllWithTrashed(string $userId): int;

    public function getLatestForUser(string $userId, int $limit = 10): Collection;
}
