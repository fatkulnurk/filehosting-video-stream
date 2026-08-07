<?php

namespace App\Services\Interfaces;

use App\Models\DownloadTask;
use App\Models\File;
use App\Models\Torrent;

interface TorrentImportServiceInterface
{
    /**
     * Reuse existing catalog bytes: create File rows for every torrent file.
     *
     * @return File[]
     */
    public function importFromCatalog(Torrent $torrent, DownloadTask $task): array;

    /**
     * Scan the shared-volume directory for a finished torrent and import all files.
     *
     * @return array{files: File[], size: int}
     */
    public function importFromDirectory(string $directory, DownloadTask $task): array;
}
