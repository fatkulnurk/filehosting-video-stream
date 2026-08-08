<?php

declare(strict_types=1);

namespace App\Jobs\Downloads;

use App\Contracts\Repositories\Downloads\DownloadTaskRepositoryInterface;
use App\Enums\DownloadTaskStatus;
use App\Models\Downloads\DownloadServer;
use App\Models\Downloads\DownloadTask;
use App\Services\Downloads\TorrentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Concurrency;
use Throwable;

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
            ->filter(fn (DownloadTask $task) => $task->status === DownloadTaskStatus::Downloading)
            ->filter(fn (DownloadTask $task) => $task->info_hash !== null);

        $server = $torrentService->active();

        // Poll each torrent's progress in parallel (each is independent remote IO).
        $results = Concurrency::run(
            $tasks->map(fn (DownloadTask $task) => fn () => $this->poll($task, $torrentService, $server))->values()->all()
        );

        foreach ($results as $result) {
            if (! $result) {
                continue;
            }

            $taskRepository->update($result['task_id'], ['progress' => $result['progress']]);

            if ($result['progress'] >= 1.0) {
                $taskRepository->update($result['task_id'], ['status' => DownloadTaskStatus::Processing]);
                ImportTorrentFileJob::dispatch($result['task_id']);
            }
        }
    }

    /**
     * @return array{task_id: string, progress: float}|null
     */
    private function poll(DownloadTask $task, TorrentService $torrentService, ?DownloadServer $server): ?array
    {
        try {
            return [
                'task_id' => $task->id,
                'progress' => $torrentService->progress($task->info_hash, $server),
            ];
        } catch (Throwable) {
            // Torrent may have been removed or the client is unreachable.
            return null;
        }
    }
}
