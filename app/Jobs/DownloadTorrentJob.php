<?php

namespace App\Jobs;

use App\Models\DownloadServer;
use App\Models\DownloadTask;
use App\Models\Torrent;
use App\Repositories\Interfaces\DownloadTaskRepositoryInterface;
use App\Services\Interfaces\TorrentCatalogServiceInterface;
use App\Services\Interfaces\TorrentImportServiceInterface;
use App\Services\TorrentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DownloadTorrentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public string $taskId,
        public ?string $serverId = null
    ) {}

    public function handle(
        DownloadTaskRepositoryInterface $taskRepository,
        TorrentCatalogServiceInterface $catalog,
        TorrentImportServiceInterface $importService,
        TorrentService $torrentService
    ): void {
        $task = $taskRepository->findById($this->taskId);

        if (! $task || $task->type->value !== 'torrent') {
            return;
        }

        $infoHash = $task->info_hash;

        if (! $infoHash) {
            $taskRepository->update($task->id, ['status' => 'failed', 'error' => 'Could not determine torrent info hash.']);

            return;
        }

        $server = $this->serverId
            ? DownloadServer::find($this->serverId)
            : $torrentService->active();

        $existing = $catalog->findByHash($infoHash);

        if ($existing && $catalog->hasFilesAvailable($existing)) {
            $this->reuse($task, $taskRepository, $catalog, $importService, $existing, $server);

            return;
        }

        $this->download($task, $taskRepository, $torrentService, $infoHash, $server);
    }

    private function reuse(
        DownloadTask $task,
        DownloadTaskRepositoryInterface $taskRepository,
        TorrentCatalogServiceInterface $catalog,
        TorrentImportServiceInterface $importService,
        Torrent $torrent,
        ?DownloadServer $server
    ): void {
        try {
            $taskRepository->update($task->id, [
                'status' => 'processing',
                'torrent_id' => $torrent->id,
            ]);

            $files = $importService->importFromCatalog($torrent, $task->fresh());

            $taskRepository->update($task->id, [
                'status' => 'completed',
                'progress' => 1.0,
                'file_id' => $files[0]->id ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Torrent reuse failed, falling back to download.', [
                'task_id' => $task->id,
                'hash' => $task->info_hash,
                'error' => $e->getMessage(),
            ]);

            $this->download($task->fresh(), $taskRepository, app(TorrentService::class), $task->info_hash, $server);
        }
    }

    private function download(
        DownloadTask $task,
        DownloadTaskRepositoryInterface $taskRepository,
        TorrentService $torrentService,
        string $infoHash,
        ?DownloadServer $server
    ): void {
        $containerDir = rtrim(
            $server?->config['container_download_dir'] ?? config('services.torrent.container_download_dir', '/downloads'),
            '/\\'
        );
        $savePath = $containerDir.DIRECTORY_SEPARATOR.$infoHash;

        try {
            $added = $torrentService->add($task->source, ['savepath' => $savePath], $server);

            if (! $added) {
                throw new \RuntimeException('Torrent client rejected the source.');
            }

            $taskRepository->update($task->id, [
                'status' => 'downloading',
                'progress' => 0,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to add torrent.', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            $taskRepository->update($task->id, [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
