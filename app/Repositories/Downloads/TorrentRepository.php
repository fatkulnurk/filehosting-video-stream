<?php

declare(strict_types=1);

namespace App\Repositories\Downloads;

use App\Contracts\Repositories\Downloads\TorrentRepositoryInterface;
use App\Models\Downloads\Torrent;
use App\Models\Downloads\TorrentFile;

class TorrentRepository implements TorrentRepositoryInterface
{
    public function create(array $data): Torrent
    {
        return Torrent::create($data);
    }

    public function findByHash(string $infoHash): ?Torrent
    {
        return Torrent::with('files.checksum')->where('info_hash', $infoHash)->first();
    }

    public function findById(string $id): ?Torrent
    {
        return Torrent::with('files.checksum')->find($id);
    }

    public function attachFile(string $torrentId, array $data): TorrentFile
    {
        return TorrentFile::create(array_merge(['torrent_id' => $torrentId], $data));
    }
}
