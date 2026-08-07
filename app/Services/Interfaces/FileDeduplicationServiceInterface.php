<?php

namespace App\Services\Interfaces;

use App\Models\FileChecksum;
use Illuminate\Http\UploadedFile;

interface FileDeduplicationServiceInterface
{
    public function findDuplicate(UploadedFile $file): ?FileChecksum;

    public function computeChecksums(UploadedFile $file): array;

    public function storeChecksum(array $checksums, string $disk, string $path, ?string $storageServerId = null, int $size = 0, string $mime = '', string $fileName = ''): FileChecksum;

    public function incrementReference(string $checksumId): void;

    public function decrementReference(string $checksumId): void;

    public function canDelete(string $checksumId): bool;

    public function deleteIfUnreferenced(string $checksumId): bool;
}
