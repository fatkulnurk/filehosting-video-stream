<?php

namespace App\Jobs;

use App\Events\FileExpired;
use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupExpiredLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        File::where('expires_at', '<', now())
            ->where('status', 'active')
            ->get()
            ->each(function (File $file) {
                event(new FileExpired($file));
                $file->update(['status' => 'expired']);
            });
    }
}
