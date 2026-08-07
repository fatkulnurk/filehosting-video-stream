<?php

namespace App\Services\Interfaces;

use App\Models\Torrent;

interface TorrentCatalogServiceInterface
{
    public function findByHash(string $infoHash): ?Torrent;

    public function hasFilesAvailable(Torrent $torrent): bool;

    public function register(string $infoHash, string $name, int $totalSize, array $files): Torrent;
}
