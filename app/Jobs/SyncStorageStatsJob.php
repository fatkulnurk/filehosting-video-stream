<?php

namespace App\Jobs;

use App\Models\StorageServer;
use App\Services\Interfaces\StorageServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncStorageStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(StorageServiceInterface $storageService): void
    {
        StorageServer::where('is_active', true)
            ->get()
            ->each(fn (StorageServer $server) => $storageService->syncUsedSize($server));
    }
}
