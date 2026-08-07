<?php

namespace App\Services\Interfaces;

use App\Models\DownloadTask;

interface DownloaderServiceInterface
{
    public function dispatch(DownloadTask $task): void;

    public function retry(DownloadTask $task): void;

    public function cancel(DownloadTask $task): bool;
}
