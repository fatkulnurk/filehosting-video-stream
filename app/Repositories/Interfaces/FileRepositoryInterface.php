<?php

namespace App\Repositories\Interfaces;

use App\Models\File;
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

    public function getUserTrashed(string $userId, int $perPage = 20): LengthAwarePaginator;

    public function getUserStorageUsage(string $userId): int;

    public function countFiles(string $userId): int;

    public function recentFiles(string $userId, int $limit = 5): Collection;

    public function getWhereIn(array $ids, ?string $userId = null): Collection;

    public function getFileCountByDisk(string $disk): int;
}
