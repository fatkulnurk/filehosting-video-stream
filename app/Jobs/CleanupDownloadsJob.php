<?php

namespace App\Jobs;

use App\Repositories\Interfaces\DownloadTaskRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupDownloadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(DownloadTaskRepositoryInterface $taskRepository): void
    {
        $cutoff = now()->subDays(config('services.downloads.retention_days', 7));

        $taskRepository->deleteOlderThan($cutoff);
    }
}
