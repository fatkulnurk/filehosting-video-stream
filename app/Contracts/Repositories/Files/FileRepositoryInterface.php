<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Files;

use App\Models\Files\File;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface FileRepositoryInterface
{
    public function create(array $data): File;

    public function findById(string $id): ?File;

    public function findByIdWithRelations(string $id): ?File;

    public function getUserFiles(string $userId, ?string $directoryId = null, ?string $search = null, int $perPage = 20): LengthAwarePaginator;

    public function update(string $id, array $data): File;

    public function delete(string $id): bool;

    public function forceDelete(string $id): bool;

    public function restore(string $id): bool;

    public function incrementViews(string $id): void;

    public function incrementDownloads(string $id): void;

    public function incrementDownloadCount(string $id): void;

    public function search(string $userId, string $query): LengthAwarePaginator;

    public function getUserFilesPaginated(string $userId, ?string $directoryId = null, ?string $search = null, int $perPage = 20): LengthAwarePaginator;

    public function getUserTrashed(string $userId, int $perPage = 20): LengthAwarePaginator;

    public function getUserStorageUsage(string $userId): int;

    public function countFiles(string $userId): int;

    public function recentFiles(string $userId, int $limit = 5): Collection;

    public function getWhereIn(array $ids, ?string $userId = null): Collection;

    public function getFileCountByDisk(string $disk): int;

    public function paginateForDirectory(string $userId, ?string $directoryId, array $params): LengthAwarePaginator;

    public function paginateTrashedForUser(string $userId, array $params): LengthAwarePaginator;

    public function findTrashedForUser(string $userId, string $id): ?File;

    public function getTrashedForUser(string $userId): Collection;

    public function paginateAdmin(array $params): LengthAwarePaginator;

    public function findForAdmin(string $id): ?File;

    public function getLatestForUser(string $userId, int $limit = 10): Collection;

    public function countAllWithTrashed(string $userId): int;

    public function getByDirectoryWithTrashed(string $directoryId): Collection;
}
