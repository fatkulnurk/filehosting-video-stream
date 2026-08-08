<?php

declare(strict_types=1);

namespace App\Services\Downloads;

use App\Contracts\Services\Downloads\UrlDownloaderInterface;
use App\Rules\Downloads\SafeDownloadUrl;
use Illuminate\Support\Facades\Http;

class UrlDownloader implements UrlDownloaderInterface
{
    public function download(string $url, string $tmpPath, int $timeout = 600): void
    {
        if (! SafeDownloadUrl::isHostSafe($url)) {
            throw new \RuntimeException('Download URL points to a private or blocked address.');
        }

        $response = Http::timeout($timeout)
            ->withOptions([
                'allow_redirects' => [
                    'max' => 5,
                    'on_redirect' => function ($request, $response, $uri) {
                        if (! SafeDownloadUrl::isHostSafe((string) $uri)) {
                            throw new \RuntimeException('Download redirects to a private or blocked address.');
                        }
                    },
                ],
            ])
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
