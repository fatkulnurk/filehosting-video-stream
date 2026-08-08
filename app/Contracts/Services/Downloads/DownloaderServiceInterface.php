<?php

declare(strict_types=1);

namespace App\Contracts\Services\Downloads;

use App\Models\Downloads\DownloadServer;
use App\Models\Downloads\DownloadTask;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DownloaderServiceInterface
{
    public function dispatch(DownloadTask $task): void;

    public function createTask(array $data): DownloadTask;

    public function retry(DownloadTask $task): void;

    public function cancel(DownloadTask $task): bool;

    public function findOwnedTask(string $userId, string $taskId): ?DownloadTask;

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator;

    public function getServers(): Collection;

    public function createServer(array $data): DownloadServer;

    public function updateServer(string $id, array $data): DownloadServer;

    public function getServerById(string $id): DownloadServer;

    public function deleteServer(string $id): bool;

    public function testServerConnection(string $id): bool;
}
