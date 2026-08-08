<?php

declare(strict_types=1);

namespace App\Services\Downloads;

use App\Contracts\Repositories\Downloads\TorrentRepositoryInterface;
use App\Contracts\Services\Downloads\TorrentCatalogServiceInterface;
use App\Models\Downloads\Torrent;
use Illuminate\Support\Facades\Storage;

class TorrentCatalogService implements TorrentCatalogServiceInterface
{
    public function __construct(
        private TorrentRepositoryInterface $torrentRepository
    ) {}

    public function findByHash(string $infoHash): ?Torrent
    {
        return $this->torrentRepository->findByHash($infoHash);
    }

    public function hasFilesAvailable(Torrent $torrent): bool
    {
        if ($torrent->files->isEmpty()) {
            return false;
        }

        foreach ($torrent->files as $torrentFile) {
            $checksum = $torrentFile->checksum;

            if (! $checksum) {
                return false;
            }

            if (! Storage::disk($checksum->disk)->exists($checksum->path)) {
                return false;
            }
        }

        return true;
    }

    public function register(string $infoHash, string $name, int $totalSize, array $files): Torrent
    {
        $existing = $this->torrentRepository->findByHash($infoHash);

        if ($existing) {
            foreach ($files as $file) {
                $this->torrentRepository->attachFile($existing->id, $file);
            }

            return $existing->fresh('files.checksum');
        }

        $torrent = $this->torrentRepository->create([
            'info_hash' => $infoHash,
            'name' => $name,
            'total_size' => $totalSize,
        ]);

        foreach ($files as $file) {
            $this->torrentRepository->attachFile($torrent->id, $file);
        }

        return $torrent->fresh('files.checksum');
    }
}
