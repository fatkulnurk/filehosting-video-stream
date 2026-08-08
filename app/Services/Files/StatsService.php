<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Contracts\Repositories\Directories\DirectoryRepositoryInterface;
use App\Contracts\Repositories\Files\FileRepositoryInterface;
use App\Contracts\Repositories\Storage\StorageServerRepositoryInterface;
use App\Contracts\Services\Files\StatsServiceInterface;
use App\Models\Files\Directory;
use App\Models\Files\File;
use App\Models\Files\FileDownload;
use App\Models\Files\FileView;
use App\Models\Users\Profile;
use App\Models\Users\User;
use Illuminate\Support\Facades\Concurrency;

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
        [$totalFiles, $totalViews, $totalDownloads, $storageUsed, $storageLimit, $directoryCount, $serverCount] = Concurrency::run([
            fn () => $this->fileRepository->countFiles($userId),
            fn () => (int) File::where('user_id', $userId)->sum('views'),
            fn () => (int) File::where('user_id', $userId)->sum('downloads'),
            fn () => (int) (Profile::where('user_id', $userId)->value('storage_used') ?? 0),
            fn () => Profile::where('user_id', $userId)->value('storage_limit'),
            fn () => $this->directoryRepository->countAll($userId),
            fn () => count($this->storageServerRepository->getActiveServers()),
        ]);

        return [
            'files' => $totalFiles,
            'views' => $totalViews,
            'downloads' => $totalDownloads,
            'storage_used' => $storageUsed,
            'storage_limit' => $storageLimit,
            'directories' => $directoryCount,
            'storage_servers' => $serverCount,
        ];
    }

    public function getGlobalStats(): array
    {
        [$files, $views, $downloads, $users, $directories, $storageUsed] = Concurrency::run([
            fn () => (int) File::count(),
            fn () => (int) File::sum('views'),
            fn () => (int) File::sum('downloads'),
            fn () => (int) User::count(),
            fn () => (int) Directory::count(),
            fn () => (int) File::join('media', function ($join) {
                $join->on('files.id', '=', 'media.model_id')
                    ->where('media.model_type', '=', File::class);
            })->sum('media.size'),
        ]);

        return [
            'files' => $files,
            'views' => $views,
            'downloads' => $downloads,
            'users' => $users,
            'directories' => $directories,
            'storage_used' => $storageUsed,
        ];
    }

    public function getFileStats(File $file): array
    {
        [$recentViews, $recentDownloads] = Concurrency::run([
            fn () => FileView::where('file_id', $file->id)->latest()->limit(10)->get(),
            fn () => FileDownload::where('file_id', $file->id)->latest()->limit(10)->get(),
        ]);

        return [
            'views' => $file->views,
            'downloads' => $file->downloads,
            'recent_views' => $recentViews,
            'recent_downloads' => $recentDownloads,
        ];
    }
}
