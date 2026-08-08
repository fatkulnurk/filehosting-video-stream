<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\ActivityLogs\ActivityLogRepositoryInterface;
use App\Contracts\Repositories\ApiKeys\ApiKeyRepositoryInterface;
use App\Contracts\Repositories\Directories\DirectoryRepositoryInterface;
use App\Contracts\Repositories\Directories\DirectoryShareRepositoryInterface;
use App\Contracts\Repositories\Downloads\DownloadTaskRepositoryInterface;
use App\Contracts\Repositories\Downloads\TorrentRepositoryInterface;
use App\Contracts\Repositories\Files\FileAccessLogRepositoryInterface;
use App\Contracts\Repositories\Files\FileChecksumRepositoryInterface;
use App\Contracts\Repositories\Files\FileFavoriteRepositoryInterface;
use App\Contracts\Repositories\Files\FileRepositoryInterface;
use App\Contracts\Repositories\Files\FileShareRepositoryInterface;
use App\Contracts\Repositories\Files\FileVersionRepositoryInterface;
use App\Contracts\Repositories\Storage\StoragePackageRepositoryInterface;
use App\Contracts\Repositories\Storage\StorageServerRepositoryInterface;
use App\Contracts\Repositories\Subscriptions\UserSubscriptionRepositoryInterface;
use App\Contracts\Repositories\Webhooks\WebhookRepositoryInterface;
use App\Contracts\Services\ActivityLogs\ActivityLogServiceInterface;
use App\Contracts\Services\Admin\AdminServiceInterface;
use App\Contracts\Services\ApiKeys\ApiKeyServiceInterface;
use App\Contracts\Services\Directories\DirectoryServiceInterface;
use App\Contracts\Services\Directories\DirectoryShareServiceInterface;
use App\Contracts\Services\Downloads\DownloaderServiceInterface;
use App\Contracts\Services\Downloads\TorrentCatalogServiceInterface;
use App\Contracts\Services\Downloads\TorrentImportServiceInterface;
use App\Contracts\Services\Downloads\UrlDownloaderInterface;
use App\Contracts\Services\Files\BatchOperationServiceInterface;
use App\Contracts\Services\Files\ChecksumServiceInterface;
use App\Contracts\Services\Files\FileAccessServiceInterface;
use App\Contracts\Services\Files\FileDeduplicationServiceInterface;
use App\Contracts\Services\Files\FileFavoriteServiceInterface;
use App\Contracts\Services\Files\FileMigrationServiceInterface;
use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Files\FileShareServiceInterface;
use App\Contracts\Services\Files\StatsServiceInterface;
use App\Contracts\Services\Files\SubtitleServiceInterface;
use App\Contracts\Services\Files\TrashServiceInterface;
use App\Contracts\Services\Files\VersioningServiceInterface;
use App\Contracts\Services\Storage\StorageQuotaServiceInterface;
use App\Contracts\Services\Storage\StorageServiceInterface;
use App\Contracts\Services\Subscriptions\SubscriptionServiceInterface;
use App\Contracts\Services\Webhooks\WebhookServiceInterface;
use App\Listeners\ActivityLogs\ActivityLogSubscriber;
use App\Listeners\Webhooks\WebhookSubscriber;
use App\Repositories\ActivityLogs\ActivityLogRepository;
use App\Repositories\ApiKeys\ApiKeyRepository;
use App\Repositories\Directories\DirectoryRepository;
use App\Repositories\Directories\DirectoryShareRepository;
use App\Repositories\Downloads\DownloadTaskRepository;
use App\Repositories\Downloads\TorrentRepository;
use App\Repositories\Files\FileAccessLogRepository;
use App\Repositories\Files\FileChecksumRepository;
use App\Repositories\Files\FileFavoriteRepository;
use App\Repositories\Files\FileRepository;
use App\Repositories\Files\FileShareRepository;
use App\Repositories\Files\FileVersionRepository;
use App\Repositories\Storage\StoragePackageRepository;
use App\Repositories\Storage\StorageServerRepository;
use App\Repositories\Subscriptions\UserSubscriptionRepository;
use App\Repositories\Webhooks\WebhookRepository;
use App\Services\ActivityLogs\ActivityLogService;
use App\Services\Admin\AdminService;
use App\Services\ApiKeys\ApiKeyService;
use App\Services\Directories\DirectoryService;
use App\Services\Directories\DirectoryShareService;
use App\Services\Downloads\DownloaderService;
use App\Services\Downloads\TorrentCatalogService;
use App\Services\Downloads\TorrentImportService;
use App\Services\Downloads\UrlDownloader;
use App\Services\Files\BatchOperationService;
use App\Services\Files\ChecksumService;
use App\Services\Files\FileAccessService;
use App\Services\Files\FileDeduplicationService;
use App\Services\Files\FileFavoriteService;
use App\Services\Files\FileMigrationService;
use App\Services\Files\FileService;
use App\Services\Files\FileShareService;
use App\Services\Files\StatsService;
use App\Services\Files\SubtitleService;
use App\Services\Files\TrashService;
use App\Services\Files\VersioningService;
use App\Services\Storage\StorageQuotaService;
use App\Services\Storage\StorageService;
use App\Services\Subscriptions\SubscriptionService;
use App\Services\Webhooks\WebhookService;
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
