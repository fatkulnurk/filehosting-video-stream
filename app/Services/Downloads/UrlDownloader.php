<?php

declare(strict_types=1);

namespace App\Services\Downloads;

use App\Contracts\Services\Downloads\UrlDownloaderInterface;
use Illuminate\Support\Facades\Http;

class UrlDownloader implements UrlDownloaderInterface
{
    public function download(string $url, string $tmpPath, int $timeout = 600): void
    {
        $response = Http::timeout($timeout)
            ->withOptions(['allow_redirects' => true])
            ->sink($tmpPath)
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("Download failed with status {$response->status()}.");
        }

        clearstatcache(true, $tmpPath);

        if ((int) filesize($tmpPath) === 0) {
            throw new \RuntimeException('Downloaded file is empty.');
        }
    }
}
