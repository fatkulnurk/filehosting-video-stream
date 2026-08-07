<?php

namespace App\Services\Interfaces;

interface UrlDownloaderInterface
{
    public function download(string $url, string $tmpPath, int $timeout = 600): void;
}
