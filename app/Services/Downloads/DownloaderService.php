<?php

declare(strict_types=1);

namespace App\Services\Downloads;

use App\Contracts\Repositories\Downloads\DownloadTaskRepositoryInterface;
use App\Contracts\Services\Downloads\DownloaderServiceInterface;
use App\Enums\DownloaderDriver;
use App\Enums\DownloadTaskStatus;
use App\Enums\DownloadTaskType;
use App\Jobs\Downloads\DownloadFromUrlJob;
use App\Jobs\Downloads\DownloadTorrentJob;
use App\Models\Downloads\DownloadServer;
use App\Models\Downloads\DownloadTask;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DownloaderService implements DownloaderServiceInterface
{
    public function __construct(
        private DownloadTaskRepositoryInterface $taskRepository,
        private TorrentService $torrentService
    ) {}

    public function dispatch(DownloadTask $task): void
    {
        $server = $this->activeServer($task->type);

        if ($task->type === DownloadTaskType::Url) {
            DownloadFromUrlJob::dispatch($task->id);
        } else {
            DownloadTorrentJob::dispatch($task->id, $server?->id);
        }
    }

    public function createTask(array $data): DownloadTask
    {
        return $this->taskRepository->create($data);
    }

    private function activeServer(DownloadTaskType $type): ?DownloadServer
    {
        if ($type === DownloadTaskType::Torrent) {
            return DownloadServer::query()
                ->where('driver', DownloaderDriver::Torrent->value)
                ->where('is_active', true)
                ->first();
        }

        return null;
    }

    public function retry(DownloadTask $task): void
    {
        $this->taskRepository->update($task->id, [
            'status' => DownloadTaskStatus::Queued,
            'progress' => 0,
            'error' => null,
            'file_id' => null,
            'torrent_id' => null,
            'info_hash' => $task->info_hash,
        ]);

        $this->dispatch($task->fresh());
    }

    public function cancel(DownloadTask $task): bool
    {
        if ($task->isFinal()) {
            return false;
        }

        if ($task->type === DownloadTaskType::Torrent && $task->info_hash) {
            try {
                app(TorrentService::class)->remove($task->info_hash, true, $this->activeServer(DownloadTaskType::Torrent));
            } catch (\Throwable) {
                // ignore remote client errors during cancellation
            }
        }

        $this->taskRepository->update($task->id, [
            'status' => DownloadTaskStatus::Cancelled,
            'error' => 'Cancelled by user',
        ]);

        return true;
    }

    public function findOwnedTask(string $userId, string $taskId): ?DownloadTask
    {
        $task = $this->taskRepository->findById($taskId);

        return $task && $task->user_id === $userId ? $task : null;
    }

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator
    {
        return $this->taskRepository->paginateForUser($userId, $params);
    }

    public function getServers(): Collection
    {
        return DownloadServer::orderBy('driver')->orderBy('name')->get();
    }

    public function createServer(array $data): DownloadServer
    {
        return DownloadServer::create($data);
    }

    public function updateServer(string $id, array $data): DownloadServer
    {
        $server = DownloadServer::findOrFail($id);
        $server->update($data);

        return $server->fresh();
    }

    public function getServerById(string $id): DownloadServer
    {
        return DownloadServer::findOrFail($id);
    }

    public function deleteServer(string $id): bool
    {
        return DownloadServer::where('id', $id)->delete() > 0;
    }

    public function testServerConnection(string $id): bool
    {
        $server = $this->getServerById($id);

        return $this->torrentService->serverReachable($server);
    }
}
