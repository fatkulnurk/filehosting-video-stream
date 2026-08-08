<?php

declare(strict_types=1);

namespace App\Contracts\Services\Downloads;

interface UrlDownloaderInterface
{
    public function download(string $url, string $tmpPath, int $timeout = 600): void;
}
