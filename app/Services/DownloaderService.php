<?php

namespace App\Services;

use App\Enums\DownloaderDriver;
use App\Enums\DownloadTaskType;
use App\Jobs\DownloadFromUrlJob;
use App\Jobs\DownloadTorrentJob;
use App\Models\DownloadServer;
use App\Models\DownloadTask;
use App\Repositories\Interfaces\DownloadTaskRepositoryInterface;
use App\Services\Interfaces\DownloaderServiceInterface;

class DownloaderService implements DownloaderServiceInterface
{
    public function __construct(
        private DownloadTaskRepositoryInterface $taskRepository
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
            'status' => 'queued',
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
            'status' => 'cancelled',
            'error' => 'Cancelled by user',
        ]);

        return true;
    }
}
