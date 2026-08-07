<?php

namespace App\Repositories\Interfaces;

use App\Models\StorageServer;
use Illuminate\Database\Eloquent\Collection;

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
}
