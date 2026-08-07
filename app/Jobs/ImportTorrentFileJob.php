<?php

namespace App\Jobs;

use App\Models\DownloadServer;
use App\Models\DownloadTask;
use App\Models\File;
use App\Repositories\Interfaces\DownloadTaskRepositoryInterface;
use App\Repositories\Interfaces\TorrentRepositoryInterface;
use App\Services\Interfaces\TorrentCatalogServiceInterface;
use App\Services\Interfaces\TorrentImportServiceInterface;
use App\Services\TorrentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportTorrentFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(
        public string $taskId
    ) {}

    public function handle(
        DownloadTaskRepositoryInterface $taskRepository,
        TorrentImportServiceInterface $importService,
        TorrentCatalogServiceInterface $catalog,
        TorrentRepositoryInterface $torrentRepository,
        TorrentService $torrentService
    ): void {
        $task = $taskRepository->findById($this->taskId);

        if (! $task || $task->type->value !== 'torrent' || ! $task->info_hash) {
            return;
        }

        $server = $torrentService->active();

        $downloadDir = rtrim(
            $server?->config['download_dir'] ?? config('services.torrent.download_dir', storage_path('app/downloader/incoming')),
            '/\\'
        );
        $sourceDir = $downloadDir.DIRECTORY_SEPARATOR.$task->info_hash;

        try {
            $result = $importService->importFromDirectory($sourceDir, $task);

            // Register in catalog for future dedup.
            $filesData = [];

            foreach ($result['files'] as $row) {
                /** @var File $file */
                $file = $row['file'];
                $checksum = $file->checksum;

                if (! $checksum) {
                    continue;
                }

                $media = $file->getFirstMedia();

                $filesData[] = [
                    'file_checksum_id' => $checksum->id,
                    'relative_path' => $row['relative_path'],
                    'file_name' => $media?->file_name ?? $file->name,
                    'size' => $checksum->size,
                ];
            }

            $torrent = $catalog->register(
                $task->info_hash,
                $this->torrentName($task, $torrentService, $server),
                $result['size'],
                $filesData
            );

            $taskRepository->update($task->id, [
                'status' => 'completed',
                'progress' => 1.0,
                'torrent_id' => $torrent->id,
                'file_id' => $result['files'][0]['file']->id ?? null,
            ]);

            $this->cleanup($task->info_hash, $sourceDir, $torrentService, $server);
        } catch (\Throwable $e) {
            Log::error('Torrent import failed.', [
                'task_id' => $task->id,
                'hash' => $task->info_hash,
                'error' => $e->getMessage(),
            ]);

            $taskRepository->update($task->id, [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function torrentName(DownloadTask $task, TorrentService $torrentService, ?DownloadServer $server = null): string
    {
        try {
            return $torrentService->get($task->info_hash, $server)->name ?: $task->source;
        } catch (\Throwable) {
            return $task->source;
        }
    }

    private function cleanup(string $infoHash, string $sourceDir, TorrentService $torrentService, ?DownloadServer $server = null): void
    {
        try {
            $torrentService->remove($infoHash, true, $server);
        } catch (\Throwable) {
            // ignore removal errors; temp dir cleanup below still applies
        }

        // Best-effort removal of the per-hash temp folder from the shared volume.
        try {
            if (is_dir($sourceDir)) {
                $disk = Storage::build(['driver' => 'local', 'root' => dirname($sourceDir)]);
                $disk->deleteDirectory(basename($sourceDir));
            }
        } catch (\Throwable) {
            // ignore
        }
    }
}
