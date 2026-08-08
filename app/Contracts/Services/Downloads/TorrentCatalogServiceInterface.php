<?php

declare(strict_types=1);

namespace App\Contracts\Services\Downloads;

use App\Models\Downloads\Torrent;

interface TorrentCatalogServiceInterface
{
    public function findByHash(string $infoHash): ?Torrent;

    public function hasFilesAvailable(Torrent $torrent): bool;

    public function register(string $infoHash, string $name, int $totalSize, array $files): Torrent;
}
