<?php

namespace App\Services;

use App\Events\FileUploaded;
use App\Exceptions\StorageQuotaExceededException;
use App\Models\File;
use App\Models\FileChecksum;
use App\Models\StorageServer;
use App\Repositories\Interfaces\FileRepositoryInterface;
use App\Repositories\Interfaces\StorageServerRepositoryInterface;
use App\Services\Interfaces\FileDeduplicationServiceInterface;
use App\Services\Interfaces\FileServiceInterface;
use App\Services\Interfaces\StorageQuotaServiceInterface;
use App\Services\Interfaces\StorageServiceInterface;
use App\Services\Interfaces\SubscriptionServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FileService implements FileServiceInterface
{
    public function __construct(
        private FileRepositoryInterface $fileRepository,
        private StorageServerRepositoryInterface $storageServerRepository,
        private StorageQuotaServiceInterface $quotaService,
        private StorageServiceInterface $storageService,
        private FileDeduplicationServiceInterface $dedupService,
        private SubscriptionServiceInterface $subscriptionService
    ) {}

    public function store(UploadedFile $file, ?string $directoryId, string $userId, ?string $diskName = null): File
    {
        $this->checkQuota($userId, $file->getSize());
        $this->checkPackageLimits($userId, $file);

        $disk = $diskName ?? $this->storageService->getSystemDiskName();

        $existingChecksum = $this->dedupService->findDuplicate($file);

        if ($existingChecksum) {
            return $this->storeFromChecksum($existingChecksum, $directoryId, $userId);
        }

        $fileModel = $this->fileRepository->create([
            'user_id' => $userId,
            'directory_id' => $directoryId,
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'slug' => Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: Str::lower(Str::random(8)),
        ]);

        $checksums = $this->dedupService->computeChecksums($file);
        $size = $file->getSize();
        $mimeType = $file->getMimeType();
        $originalName = $file->getClientOriginalName();

        $media = $fileModel->addMedia($file)
            ->usingName($originalName)
            ->withCustomProperties([
                'checksum' => $checksums['sha256'],
                'uploaded_by' => $userId,
            ])
            ->toMediaCollection('default', $disk);

        $storageServerId = $this->storageServerRepository->findByName($disk)?->id;

        $this->dedupService->storeChecksum($checksums, $disk, $media->getPathRelativeToRoot(), $storageServerId, $size, $mimeType, $originalName);

        Cache::forget("storage_usage:{$userId}");

        event(new FileUploaded($fileModel));

        return $fileModel;
    }

    public function storeFromChecksum(FileChecksum $checksum, ?string $directoryId, string $userId): File
    {
        $this->checkQuota($userId, $checksum->size);

        $fileModel = $this->fileRepository->create([
            'user_id' => $userId,
            'directory_id' => $directoryId,
            'name' => pathinfo($checksum->file_name, PATHINFO_FILENAME),
            'slug' => Str::slug(pathinfo($checksum->file_name, PATHINFO_FILENAME)) ?: Str::lower(Str::random(8)),
            'file_checksum_id' => $checksum->id,
        ]);

        $fileModel->addMediaFromDisk($checksum->path, $checksum->disk)
            ->usingName($checksum->file_name)
            ->toMediaCollection('default');

        $this->dedupService->incrementReference($checksum->id);

        Cache::forget("storage_usage:{$userId}");

        event(new FileUploaded($fileModel));

        return $fileModel;
    }

    public function delete(File $file): bool
    {
        $deleted = $this->fileRepository->delete($file->id);

        if ($deleted) {
            if ($file->file_checksum_id) {
                $this->dedupService->decrementReference($file->file_checksum_id);
                $this->dedupService->deleteIfUnreferenced($file->file_checksum_id);
            }

            Cache::forget("storage_usage:{$file->user_id}");
            $this->quotaService->recalculate($file->user_id);
        }

        return $deleted;
    }

    public function forceDelete(File $file): bool
    {
        $file->clearMediaCollection('default');
        $file->clearMediaCollection('versions');
        $file->clearMediaCollection('subtitles');

        $deleted = $this->fileRepository->forceDelete($file->id);

        if ($deleted) {
            if ($file->file_checksum_id) {
                $this->dedupService->decrementReference($file->file_checksum_id);
                $this->dedupService->deleteIfUnreferenced($file->file_checksum_id);
            }

            Cache::forget("storage_usage:{$file->user_id}");
            $this->quotaService->recalculate($file->user_id);
        }

        return $deleted;
    }

    public function restore(File $file): bool
    {
        $restored = $this->fileRepository->restore($file->id);

        if ($restored) {
            if ($file->file_checksum_id) {
                $this->dedupService->incrementReference($file->file_checksum_id);
            }

            Cache::forget("storage_usage:{$file->user_id}");
        }

        return $restored;
    }

    public function move(File $file, ?string $newDirectoryId): File
    {
        return $this->fileRepository->update($file->id, [
            'directory_id' => $newDirectoryId,
        ]);
    }

    public function rename(File $file, string $name): File
    {
        return $this->fileRepository->update($file->id, [
            'name' => $name,
            'slug' => Str::slug($name) ?: $file->slug,
        ]);
    }

    public function getById(string $id): ?File
    {
        return $this->fileRepository->findByIdWithRelations($id);
    }

    public function getPublicFile(string $id): ?File
    {
        $file = $this->fileRepository->findByIdWithRelations($id);

        if (! $file || ! $file->is_public || $file->isExpired() || $file->trashed()) {
            return null;
        }

        return $file;
    }

    public function incrementViews(File $file): void
    {
        $this->fileRepository->incrementViews($file->id);
    }

    public function incrementDownloads(File $file): void
    {
        $this->fileRepository->incrementDownloads($file->id);
        $this->fileRepository->incrementDownloadCount($file->id);
    }

    public function updateSettings(File $file, array $data): File
    {
        return $this->fileRepository->update($file->id, $data);
    }

    private function checkQuota(string $userId, int $fileSize): void
    {
        if (! $this->quotaService->canUpload($userId, $fileSize)) {
            $remaining = $this->quotaService->getRemaining($userId);

            throw new StorageQuotaExceededException(
                'Storage quota exceeded. Remaining: '.format_bytes($remaining ?? 0)
                .', File size: '.format_bytes($fileSize)
            );
        }
    }

    private function checkPackageLimits(string $userId, UploadedFile $file): void
    {
        $maxFileSize = $this->subscriptionService->getUserMaxFileSize($userId);

        if ($maxFileSize && $file->getSize() > $maxFileSize) {
            throw new StorageQuotaExceededException(
                'File size exceeds your plan limit of '.format_bytes($maxFileSize)
            );
        }
    }

    public function resolveStorageServer(string $disk): ?StorageServer
    {
        return $this->storageServerRepository->findByName($disk);
    }
}
