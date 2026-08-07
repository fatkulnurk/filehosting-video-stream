<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileDownload;
use App\Models\FileView;
use App\Repositories\Interfaces\DirectoryRepositoryInterface;
use App\Repositories\Interfaces\FileRepositoryInterface;
use App\Repositories\Interfaces\StorageServerRepositoryInterface;
use App\Services\Interfaces\StatsServiceInterface;

class StatsService implements StatsServiceInterface
{
    public function __construct(
        private FileRepositoryInterface $fileRepository,
        private DirectoryRepositoryInterface $directoryRepository,
        private StorageServerRepositoryInterface $storageServerRepository
    ) {}

    public function recordView(File $file, ?string $referer = null): void
    {
        $this->fileRepository->incrementViews($file->id);

        FileView::create([
            'file_id' => $file->id,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => $referer ?? request()->header('referer'),
        ]);
    }

    public function recordDownload(File $file): void
    {
        $this->fileRepository->incrementDownloads($file->id);

        FileDownload::create([
            'file_id' => $file->id,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getDashboardStats(string $userId): array
    {
        $totalFiles = $this->fileRepository->countFiles($userId);
        $totalViews = (int) \App\Models\File::where('user_id', $userId)->sum('views');
        $totalDownloads = (int) \App\Models\File::where('user_id', $userId)->sum('downloads');
        $storageUsed = (int) \App\Models\Profile::where('user_id', $userId)->value('storage_used') ?? 0;

        return [
            'files' => $totalFiles,
            'views' => $totalViews,
            'downloads' => $totalDownloads,
            'storage_used' => $storageUsed,
            'storage_limit' => \App\Models\Profile::where('user_id', $userId)->value('storage_limit'),
            'directories' => $this->directoryRepository->countAll($userId),
            'storage_servers' => count($this->storageServerRepository->getActiveServers()),
        ];
    }

    public function getGlobalStats(): array
    {
        return [
            'files' => (int) \App\Models\File::count(),
            'views' => (int) \App\Models\File::sum('views'),
            'downloads' => (int) \App\Models\File::sum('downloads'),
            'users' => (int) \App\Models\User::count(),
            'directories' => (int) \App\Models\Directory::count(),
            'storage_used' => (int) \App\Models\File::join('media', function ($join) {
                $join->on('files.id', '=', 'media.model_id')
                    ->where('media.model_type', '=', File::class);
            })->sum('media.size'),
        ];
    }

    public function getFileStats(File $file): array
    {
        return [
            'views' => $file->views,
            'downloads' => $file->downloads,
            'recent_views' => FileView::where('file_id', $file->id)->latest()->limit(10)->get(),
            'recent_downloads' => FileDownload::where('file_id', $file->id)->latest()->limit(10)->get(),
        ];
    }
}
