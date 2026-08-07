<?php

namespace App\Services;

use App\Models\Directory;
use App\Models\DownloadTask;
use App\Models\File;
use App\Models\Torrent;
use App\Services\Interfaces\DirectoryServiceInterface;
use App\Services\Interfaces\FileServiceInterface;
use App\Services\Interfaces\TorrentImportServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class TorrentImportService implements TorrentImportServiceInterface
{
    public function __construct(
        private FileServiceInterface $fileService
    ) {}

    public function importFromCatalog(Torrent $torrent, DownloadTask $task): array
    {
        $files = [];

        foreach ($torrent->files as $torrentFile) {
            $checksum = $torrentFile->checksum;

            if (! $checksum) {
                continue;
            }

            $parentId = $this->ensureDirectoryPath(
                dirname($torrentFile->relative_path),
                $task->user_id,
                $task->directory_id
            );

            $files[] = $this->fileService->storeFromChecksum($checksum, $parentId, $task->user_id);
        }

        return $files;
    }

    /**
     * Scan the shared-volume directory for a finished torrent and import all files.
     *
     * @return array{files: array<int, array{file: File, relative_path: string}>, size: int}
     */
    public function importFromDirectory(string $directory, DownloadTask $task): array
    {
        if (! is_dir($directory)) {
            throw new \RuntimeException("Torrent download directory not found: {$directory}");
        }

        $scanRoot = $this->resolveScanRoot($directory);
        $imported = [];
        $totalSize = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($scanRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            if (! $item->isFile()) {
                continue;
            }

            $relative = ltrim(substr($item->getPathname(), strlen($scanRoot)), '/\\');
            $relative = str_replace('\\', '/', $relative);

            $parentId = $this->ensureDirectoryPath(dirname($relative), $task->user_id, $task->directory_id);

            $uploadedFile = new UploadedFile($item->getPathname(), $item->getFilename(), mime_content_type($item->getPathname()) ?: 'application/octet-stream', $item->getSize(), 0, true);

            $file = $this->fileService->store($uploadedFile, $parentId, $task->user_id, $task->disk);

            $imported[] = ['file' => $file, 'relative_path' => $relative];
            $totalSize += $item->getSize();
        }

        if (empty($imported)) {
            throw new \RuntimeException('No files found in torrent download directory.');
        }

        return ['files' => $imported, 'size' => $totalSize];
    }

    private function resolveScanRoot(string $directory): string
    {
        $entries = array_values(array_filter(
            scandir($directory) ?: [],
            fn (string $name) => $name !== '.' && $name !== '..'
        ));

        // Transmission creates a single top-level folder for multi-file torrents.
        // Strip it so the preserved structure is exactly the torrent's internal one.
        if (count($entries) === 1 && is_dir($directory.DIRECTORY_SEPARATOR.$entries[0])) {
            return rtrim($directory.DIRECTORY_SEPARATOR.$entries[0], '/\\');
        }

        return $directory;
    }

    private function ensureDirectoryPath(string $relativeDir, string $userId, ?string $parentId): ?string
    {
        if ($relativeDir === '.' || $relativeDir === '' || $relativeDir === '/') {
            return $parentId;
        }

        $current = $parentId;

        foreach (explode('/', $relativeDir) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            $slug = Str::slug($segment);

            $dir = Directory::where('user_id', $userId)
                ->where('parent_id', $current)
                ->where('slug', $slug)
                ->first();

            if (! $dir) {
                $dir = app(DirectoryServiceInterface::class)->create($segment, $current, $userId);
            }

            $current = $dir->id;
        }

        return $current;
    }
}
