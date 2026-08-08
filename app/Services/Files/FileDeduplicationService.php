<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Contracts\Repositories\Files\FileChecksumRepositoryInterface;
use App\Contracts\Services\Files\ChecksumServiceInterface;
use App\Contracts\Services\Files\FileDeduplicationServiceInterface;
use App\Models\Files\FileChecksum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileDeduplicationService implements FileDeduplicationServiceInterface
{
    public function __construct(
        private FileChecksumRepositoryInterface $checksumRepo,
        private ChecksumServiceInterface $checksumService
    ) {}

    public function findDuplicate(UploadedFile $file): ?FileChecksum
    {
        $crc32 = $this->checksumService->crc32($file);
        $sha256 = $this->checksumService->sha256($file);

        $candidates = $this->checksumRepo->findByCrc32($crc32, $file->getSize());

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates->first(fn (FileChecksum $checksum) => $checksum->sha256 === $sha256);
    }

    public function computeChecksums(UploadedFile $file): array
    {
        return $this->checksumService->calculate($file);
    }

    public function storeChecksum(array $checksums, string $disk, string $path, ?string $storageServerId = null, int $size = 0, string $mime = '', string $fileName = ''): FileChecksum
    {
        return $this->checksumRepo->create([
            'crc32' => $checksums['crc32'],
            'md5' => $checksums['md5'],
            'sha1' => $checksums['sha1'],
            'sha256' => $checksums['sha256'],
            'sha512' => $checksums['sha512'],
            'storage_server_id' => $storageServerId,
            'disk' => $disk,
            'path' => $path,
            'size' => $size,
            'mime_type' => $mime,
            'file_name' => $fileName,
            'reference_count' => 1,
        ]);
    }

    public function incrementReference(string $checksumId): void
    {
        $this->checksumRepo->incrementReference($checksumId);
    }

    public function decrementReference(string $checksumId): void
    {
        $this->checksumRepo->decrementReference($checksumId);
    }

    public function canDelete(string $checksumId): bool
    {
        $checksum = $this->checksumRepo->findById($checksumId);

        return $checksum && $checksum->reference_count <= 1;
    }

    public function deleteIfUnreferenced(string $checksumId): bool
    {
        $checksum = $this->checksumRepo->findById($checksumId);

        if (! $checksum || $checksum->reference_count > 1) {
            return false;
        }

        try {
            Storage::disk($checksum->disk)->delete($checksum->path);
        } catch (\Throwable) {
            // ignore delete errors on remote disks
        }

        return $checksum->delete() !== false;
    }
}
