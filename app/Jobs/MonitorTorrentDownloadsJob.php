<?php

namespace App\Jobs;

use App\Repositories\Interfaces\DownloadTaskRepositoryInterface;
use App\Services\TorrentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MonitorTorrentDownloadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function handle(
        DownloadTaskRepositoryInterface $taskRepository,
        TorrentService $torrentService
    ): void {
        $tasks = $taskRepository->getActiveByType('torrent')
            ->filter(fn ($task) => $task->status->value === 'downloading');

        foreach ($tasks as $task) {
            if (! $task->info_hash) {
                continue;
            }

            $server = $torrentService->active();

            try {
                $progress = $torrentService->progress($task->info_hash, $server);

                $taskRepository->update($task->id, ['progress' => $progress]);

                if ($progress >= 1.0) {
                    $taskRepository->update($task->id, ['status' => 'processing']);
                    ImportTorrentFileJob::dispatch($task->id);
                }
            } catch (\Throwable $e) {
                // Torrent may have been removed or the client is unreachable.
            }
        }
    }
}
