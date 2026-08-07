<?php

namespace App\Services\Interfaces;

use App\Models\StorageServer;

interface StorageServiceInterface
{
    public function registerDisk(StorageServer $server): void;

    public function registerAllDisks(): void;

    public function getStats(string $userId): array;

    public function syncUsedSize(StorageServer $server): void;

    public function addStorage(array $data): StorageServer;

    public function updateStorage(string $id, array $data): StorageServer;

    public function deleteStorage(string $id): bool;

    public function deactivateStorage(StorageServer $server, ?string $targetStorageId = null): bool;

    public function getDefaultDiskName(string $userId): string;

    public function getSystemDiskName(): string;

    public function getGlobalStats(): array;
}
