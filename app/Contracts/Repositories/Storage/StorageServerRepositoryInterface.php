<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Storage;

use App\Models\Storage\StorageServer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface StorageServerRepositoryInterface
{
    public function create(array $data): StorageServer;

    public function findById(string $id): ?StorageServer;

    public function getUserStorageServers(string $userId): Collection;

    public function getActiveServers(): Collection;

    public function update(string $id, array $data): StorageServer;

    public function delete(string $id): bool;

    public function getStats(string $userId): array;

    public function getPrimary(string $userId): ?StorageServer;

    public function getLeastUtilizedActive(): ?StorageServer;

    public function findByName(string $name): ?StorageServer;

    public function getCountForUser(string $userId): int;

    public function getGlobalStats(): array;

    public function paginateServers(array $params): LengthAwarePaginator;

    public function getBackupOptions(): Collection;

    public function getLatest(int $limit = 10): Collection;
}
