<?php

namespace App\Repositories\Interfaces;

use App\Models\Torrent;
use App\Models\TorrentFile;

interface TorrentRepositoryInterface
{
    public function create(array $data): Torrent;

    public function findByHash(string $infoHash): ?Torrent;

    public function findById(string $id): ?Torrent;

    public function attachFile(string $torrentId, array $data): TorrentFile;
}
