<?php

namespace App\Jobs;

use App\Services\Interfaces\ActivityLogServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupOldLogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $days = 90) {}

    public function handle(ActivityLogServiceInterface $logService): void
    {
        $logService->cleanupOld($this->days);
    }
}
