<?php

namespace App\Services;

use App\Events\FileDeleted;
use App\Events\FileRestored;
use App\Models\File;
use App\Repositories\Interfaces\FileRepositoryInterface;
use App\Services\Interfaces\FileDeduplicationServiceInterface;
use App\Services\Interfaces\StorageQuotaServiceInterface;
use App\Services\Interfaces\TrashServiceInterface;
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

        $files = \App\Models\File::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days))
            ->get();

        foreach ($files as $file) {
            $this->forceDeleteFile($file);
            $count++;
        }

        return $count;
    }
}
