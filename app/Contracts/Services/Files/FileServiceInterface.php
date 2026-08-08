<?php

declare(strict_types=1);

namespace App\Contracts\Services\Files;

use App\Models\Files\File;
use App\Models\Files\FileChecksum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

interface FileServiceInterface
{
    public function store(UploadedFile $file, ?string $directoryId, string $userId, ?string $diskName = null): File;

    public function storeFromChecksum(FileChecksum $checksum, ?string $directoryId, string $userId): File;

    public function getPaginated(string $userId, ?string $directoryId = null, ?string $search = null, int $perPage = 20): LengthAwarePaginator;

    public function getRecentFiles(string $userId, int $limit = 5): Collection;

    public function delete(File $file): bool;

    public function forceDelete(File $file): bool;

    public function restore(File $file): bool;

    public function move(File $file, ?string $newDirectoryId): File;

    public function rename(File $file, string $name): File;

    public function getById(string $id): ?File;

    public function getPublicFile(string $id): ?File;

    public function getOwnedFile(string $userId, string $id): File;

    public function getTrashedFile(string $userId, string $id): File;

    public function getFileForAdmin(string $id): File;

    public function paginateForDirectory(string $userId, ?string $directoryId, array $params): LengthAwarePaginator;

    public function paginateTrashedForUser(string $userId, array $params): LengthAwarePaginator;

    public function paginateAllForAdmin(array $params): LengthAwarePaginator;

    public function getLatestForUser(string $userId, int $limit = 10): Collection;

    public function countAllWithTrashed(string $userId): int;

    public function getFilesInDirectoryWithTrashed(string $directoryId): Collection;

    public function incrementViews(File $file): void;

    public function incrementDownloads(File $file): void;

    public function updateSettings(File $file, array $data): File;
}
