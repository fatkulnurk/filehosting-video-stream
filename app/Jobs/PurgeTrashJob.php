<?php

namespace App\Jobs;

use App\Services\Interfaces\TrashServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurgeTrashJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $days = 30) {}

    public function handle(TrashServiceInterface $trashService): void
    {
        $trashService->purgeOlderThan($this->days);
    }
}
