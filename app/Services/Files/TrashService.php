<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Contracts\Repositories\Files\FileRepositoryInterface;
use App\Contracts\Services\Files\FileDeduplicationServiceInterface;
use App\Contracts\Services\Files\TrashServiceInterface;
use App\Contracts\Services\Storage\StorageQuotaServiceInterface;
use App\Events\Files\FileDeleted;
use App\Events\Files\FileRestored;
use App\Models\Files\File;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class TrashService implements TrashServiceInterface
{
    public function __construct(
        private FileRepositoryInterface $fileRepository,
        private FileDeduplicationServiceInterface $dedupService,
        private StorageQuotaServiceInterface $quotaService
    ) {}

    public function softDeleteFile(File $file): bool
    {
        $deleted = $this->fileRepository->delete($file->id);

        if ($deleted) {
            event(new FileDeleted($file));

            if ($file->file_checksum_id) {
                $this->dedupService->decrementReference($file->file_checksum_id);
            }

            Cache::forget("storage_usage:{$file->user_id}");
        }

        return $deleted;
    }

    public function restoreFile(File $file): bool
    {
        $restored = $this->fileRepository->restore($file->id);

        if ($restored) {
            event(new FileRestored($file->fresh()));

            if ($file->file_checksum_id) {
                $this->dedupService->incrementReference($file->file_checksum_id);
            }

            Cache::forget("storage_usage:{$file->user_id}");
        }

        return $restored;
    }

    public function forceDeleteFile(File $file): bool
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

    public function getTrashedFiles(string $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->fileRepository->getUserTrashed($userId, $perPage);
    }

    public function purgeOlderThan(int $days): int
    {
        $count = 0;

        $files = File::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days))
            ->get();

        foreach ($files as $file) {
            $this->forceDeleteFile($file);
            $count++;
        }

        return $count;
    }

    public function purgeTrashed(string $userId): int
    {
        $files = $this->fileRepository->getTrashedForUser($userId);

        $count = 0;

        foreach ($files as $file) {
            $this->forceDeleteFile($file);
            $count++;
        }

        return $count;
    }
}
