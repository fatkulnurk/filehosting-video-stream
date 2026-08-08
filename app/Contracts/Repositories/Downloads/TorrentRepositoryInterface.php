<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Downloads;

use App\Models\Downloads\Torrent;
use App\Models\Downloads\TorrentFile;

interface TorrentRepositoryInterface
{
    public function create(array $data): Torrent;

    public function findByHash(string $infoHash): ?Torrent;

    public function findById(string $id): ?Torrent;

    public function attachFile(string $torrentId, array $data): TorrentFile;
}
