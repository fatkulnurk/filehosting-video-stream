<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileVersion;
use App\Repositories\Interfaces\FileVersionRepositoryInterface;
use App\Services\Interfaces\StorageQuotaServiceInterface;
use App\Services\Interfaces\VersioningServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VersioningService implements VersioningServiceInterface
{
    public function __construct(
        private FileVersionRepositoryInterface $versionRepo,
        private StorageQuotaServiceInterface $quotaService
    ) {}

    public function createVersion(File $file, UploadedFile $uploadedFile, string $userId, ?string $changelog = null): FileVersion
    {
        if (! $this->quotaService->canUpload($userId, $uploadedFile->getSize())) {
            throw new \App\Exceptions\StorageQuotaExceededException('Storage quota exceeded');
        }

        $currentMedia = $file->getFirstMedia();

        if (! $currentMedia) {
            throw new \InvalidArgumentException('File has no media to version');
        }

        $disk = $currentMedia->disk;

        // Save current file as a version record (path masih ada di disk)
        $nextVersion = ($this->versionRepo->getLatestVersion($file->id)?->version ?? 0) + 1;

        $version = $this->versionRepo->create([
            'file_id' => $file->id,
            'version' => $nextVersion,
            'name' => $currentMedia->file_name,
            'path' => $currentMedia->getPathRelativeToRoot(),
            'disk' => $currentMedia->disk,
            'size' => $currentMedia->size,
            'checksum' => $currentMedia->getCustomProperty('checksum'),
            'changelog' => $changelog,
            'uploaded_by' => $userId,
        ]);

        // Replace main media with new upload (on same disk)
        $file->addMedia($uploadedFile)
            ->usingName($uploadedFile->getClientOriginalName())
            ->withCustomProperties([
                'checksum' => hash_file('sha256', $uploadedFile->getRealPath()),
                'uploaded_by' => $userId,
            ])
            ->toMediaCollection('default', $disk);

        $currentMedia->delete();

        Cache::forget("storage_usage:{$file->user_id}");

        return $version;
    }

    public function restore(File $file, string $versionId): FileVersion
    {
        $version = $this->versionRepo->findById($versionId);

        if (! $version || $version->file_id !== $file->id) {
            throw new \InvalidArgumentException('Version not found');
        }

        // Current media menjadi versi terbaru
        $currentMedia = $file->getFirstMedia();

        if ($currentMedia) {
            $nextVersion = ($this->versionRepo->getLatestVersion($file->id)?->version ?? 0) + 1;

            $this->versionRepo->create([
                'file_id' => $file->id,
                'version' => $nextVersion,
                'name' => $currentMedia->file_name,
                'path' => $currentMedia->getPathRelativeToRoot(),
                'disk' => $currentMedia->disk,
                'size' => $currentMedia->size,
                'checksum' => $currentMedia->getCustomProperty('checksum'),
                'changelog' => 'Rollback to version '.$version->version,
                'uploaded_by' => auth()->id(),
            ]);

            $currentMedia->delete();
        }

        // Restore version file ke main collection
        $file->addMediaFromDisk($version->path, $version->disk)
            ->usingName($version->name)
            ->toMediaCollection('default');

        // Update file name
        $file->update([
            'name' => pathinfo($version->name, PATHINFO_FILENAME),
            'slug' => Str::slug(pathinfo($version->name, PATHINFO_FILENAME)) ?: $file->slug,
        ]);

        Cache::forget("storage_usage:{$file->user_id}");

        return $version;
    }

    public function getVersions(File $file): \Illuminate\Database\Eloquent\Collection
    {
        return $this->versionRepo->getForFile($file->id);
    }

    public function delete(File $file, string $versionId): bool
    {
        $version = $this->versionRepo->findById($versionId);

        if (! $version || $version->file_id !== $file->id) {
            return false;
        }

        try {
            \Illuminate\Support\Facades\Storage::disk($version->disk)->delete($version->path);
        } catch (\Throwable) {
            // ignore
        }

        return $this->versionRepo->delete($versionId);
    }
}
