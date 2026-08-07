<?php

namespace App\Providers;

use App\Listeners\ActivityLogSubscriber;
use App\Listeners\WebhookSubscriber;
use App\Repositories\ActivityLogRepository;
use App\Repositories\ApiKeyRepository;
use App\Repositories\DirectoryRepository;
use App\Repositories\DirectoryShareRepository;
use App\Repositories\DownloadTaskRepository;
use App\Repositories\FileAccessLogRepository;
use App\Repositories\FileChecksumRepository;
use App\Repositories\FileFavoriteRepository;
use App\Repositories\FileRepository;
use App\Repositories\FileShareRepository;
use App\Repositories\FileVersionRepository;
use App\Repositories\Interfaces\ActivityLogRepositoryInterface;
use App\Repositories\Interfaces\ApiKeyRepositoryInterface;
use App\Repositories\Interfaces\DirectoryRepositoryInterface;
use App\Repositories\Interfaces\DirectoryShareRepositoryInterface;
use App\Repositories\Interfaces\DownloadTaskRepositoryInterface;
use App\Repositories\Interfaces\FileAccessLogRepositoryInterface;
use App\Repositories\Interfaces\FileChecksumRepositoryInterface;
use App\Repositories\Interfaces\FileFavoriteRepositoryInterface;
use App\Repositories\Interfaces\FileRepositoryInterface;
use App\Repositories\Interfaces\FileShareRepositoryInterface;
use App\Repositories\Interfaces\FileVersionRepositoryInterface;
use App\Repositories\Interfaces\StoragePackageRepositoryInterface;
use App\Repositories\Interfaces\StorageServerRepositoryInterface;
use App\Repositories\Interfaces\TorrentRepositoryInterface;
use App\Repositories\Interfaces\UserSubscriptionRepositoryInterface;
use App\Repositories\Interfaces\WebhookRepositoryInterface;
use App\Repositories\StoragePackageRepository;
use App\Repositories\StorageServerRepository;
use App\Repositories\TorrentRepository;
use App\Repositories\UserSubscriptionRepository;
use App\Repositories\WebhookRepository;
use App\Services\ActivityLogService;
use App\Services\AdminService;
use App\Services\ApiKeyService;
use App\Services\BatchOperationService;
use App\Services\ChecksumService;
use App\Services\DirectoryService;
use App\Services\DirectoryShareService;
use App\Services\DownloaderService;
use App\Services\FileAccessService;
use App\Services\FileDeduplicationService;
use App\Services\FileFavoriteService;
use App\Services\FileMigrationService;
use App\Services\FileService;
use App\Services\FileShareService;
use App\Services\Interfaces\ActivityLogServiceInterface;
use App\Services\Interfaces\AdminServiceInterface;
use App\Services\Interfaces\ApiKeyServiceInterface;
use App\Services\Interfaces\BatchOperationServiceInterface;
use App\Services\Interfaces\ChecksumServiceInterface;
use App\Services\Interfaces\DirectoryServiceInterface;
use App\Services\Interfaces\DirectoryShareServiceInterface;
use App\Services\Interfaces\DownloaderServiceInterface;
use App\Services\Interfaces\FileAccessServiceInterface;
use App\Services\Interfaces\FileDeduplicationServiceInterface;
use App\Services\Interfaces\FileFavoriteServiceInterface;
use App\Services\Interfaces\FileMigrationServiceInterface;
use App\Services\Interfaces\FileServiceInterface;
use App\Services\Interfaces\FileShareServiceInterface;
use App\Services\Interfaces\StatsServiceInterface;
use App\Services\Interfaces\StorageQuotaServiceInterface;
use App\Services\Interfaces\StorageServiceInterface;
use App\Services\Interfaces\SubscriptionServiceInterface;
use App\Services\Interfaces\SubtitleServiceInterface;
use App\Services\Interfaces\TorrentCatalogServiceInterface;
use App\Services\Interfaces\TorrentImportServiceInterface;
use App\Services\Interfaces\TrashServiceInterface;
use App\Services\Interfaces\UrlDownloaderInterface;
use App\Services\Interfaces\VersioningServiceInterface;
use App\Services\Interfaces\WebhookServiceInterface;
use App\Services\StatsService;
use App\Services\StorageQuotaService;
use App\Services\StorageService;
use App\Services\SubscriptionService;
use App\Services\SubtitleService;
use App\Services\TorrentCatalogService;
use App\Services\TorrentImportService;
use App\Services\TrashService;
use App\Services\UrlDownloader;
use App\Services\VersioningService;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->bind(FileRepositoryInterface::class, FileRepository::class);
        $this->app->bind(DirectoryRepositoryInterface::class, DirectoryRepository::class);
        $this->app->bind(StorageServerRepositoryInterface::class, StorageServerRepository::class);
        $this->app->bind(StoragePackageRepositoryInterface::class, StoragePackageRepository::class);
        $this->app->bind(UserSubscriptionRepositoryInterface::class, UserSubscriptionRepository::class);
        $this->app->bind(WebhookRepositoryInterface::class, WebhookRepository::class);
        $this->app->bind(FileChecksumRepositoryInterface::class, FileChecksumRepository::class);
        $this->app->bind(FileShareRepositoryInterface::class, FileShareRepository::class);
        $this->app->bind(DirectoryShareRepositoryInterface::class, DirectoryShareRepository::class);
        $this->app->bind(FileFavoriteRepositoryInterface::class, FileFavoriteRepository::class);
        $this->app->bind(FileAccessLogRepositoryInterface::class, FileAccessLogRepository::class);
        $this->app->bind(ActivityLogRepositoryInterface::class, ActivityLogRepository::class);
        $this->app->bind(ApiKeyRepositoryInterface::class, ApiKeyRepository::class);
        $this->app->bind(FileVersionRepositoryInterface::class, FileVersionRepository::class);
        $this->app->bind(DownloadTaskRepositoryInterface::class, DownloadTaskRepository::class);
        $this->app->bind(TorrentRepositoryInterface::class, TorrentRepository::class);

        // Services
        $this->app->bind(FileServiceInterface::class, FileService::class);
        $this->app->bind(DirectoryServiceInterface::class, DirectoryService::class);
        $this->app->bind(StorageServiceInterface::class, StorageService::class);
        $this->app->bind(StatsServiceInterface::class, StatsService::class);
        $this->app->bind(StorageQuotaServiceInterface::class, StorageQuotaService::class);
        $this->app->bind(ChecksumServiceInterface::class, ChecksumService::class);
        $this->app->bind(FileDeduplicationServiceInterface::class, FileDeduplicationService::class);
        $this->app->bind(FileMigrationServiceInterface::class, FileMigrationService::class);
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->bind(VersioningServiceInterface::class, VersioningService::class);
        $this->app->bind(TrashServiceInterface::class, TrashService::class);
        $this->app->bind(ActivityLogServiceInterface::class, ActivityLogService::class);
        $this->app->bind(WebhookServiceInterface::class, WebhookService::class);
        $this->app->bind(ApiKeyServiceInterface::class, ApiKeyService::class);
        $this->app->bind(FileShareServiceInterface::class, FileShareService::class);
        $this->app->bind(DirectoryShareServiceInterface::class, DirectoryShareService::class);
        $this->app->bind(FileFavoriteServiceInterface::class, FileFavoriteService::class);
        $this->app->bind(FileAccessServiceInterface::class, FileAccessService::class);
        $this->app->bind(BatchOperationServiceInterface::class, BatchOperationService::class);
        $this->app->bind(AdminServiceInterface::class, AdminService::class);
        $this->app->bind(SubtitleServiceInterface::class, SubtitleService::class);
        $this->app->bind(TorrentCatalogServiceInterface::class, TorrentCatalogService::class);
        $this->app->bind(TorrentImportServiceInterface::class, TorrentImportService::class);
        $this->app->bind(DownloaderServiceInterface::class, DownloaderService::class);
        $this->app->bind(UrlDownloaderInterface::class, UrlDownloader::class);
    }

    public function boot(): void
    {
        Event::subscribe(ActivityLogSubscriber::class);
        Event::subscribe(WebhookSubscriber::class);
    }
}
