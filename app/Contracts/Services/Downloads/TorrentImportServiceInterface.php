<?php

declare(strict_types=1);

namespace App\Contracts\Services\Downloads;

use App\Models\Downloads\DownloadTask;
use App\Models\Downloads\Torrent;
use App\Models\Files\File;

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
