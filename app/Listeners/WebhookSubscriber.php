<?php

namespace App\Listeners;

use App\Events\DirectoryCreated;
use App\Events\DirectoryDeleted;
use App\Events\FileDeleted;
use App\Events\FileDownloaded;
use App\Events\FileExpired;
use App\Events\FileRestored;
use App\Events\FileUploaded;
use App\Events\StorageExceeded;
use App\Events\SubscriptionExpired;
use App\Services\Interfaces\WebhookServiceInterface;
use Illuminate\Events\Dispatcher;

class WebhookSubscriber
{
    public function __construct(
        private WebhookServiceInterface $webhookService
    ) {}

    public function onFileUploaded(FileUploaded $event): void
    {
        $this->webhookService->trigger('file.uploaded', [
            'file_id' => $event->file->id,
            'name' => $event->file->name,
            'size' => $event->file->size(),
        ], $event->file->user_id);
    }

    public function onFileDownloaded(FileDownloaded $event): void
    {
        $this->webhookService->trigger('file.downloaded', [
            'file_id' => $event->file->id,
            'id' => $event->file->id,
            'ip' => request()->ip(),
        ], $event->file->user_id);
    }

    public function onFileDeleted(FileDeleted $event): void
    {
        $this->webhookService->trigger('file.deleted', [
            'file_id' => $event->file->id,
            'name' => $event->file->name,
        ], $event->file->user_id);
    }

    public function onFileRestored(FileRestored $event): void
    {
        $this->webhookService->trigger('file.restored', [
            'file_id' => $event->file->id,
            'name' => $event->file->name,
        ], $event->file->user_id);
    }

    public function onDirectoryCreated(DirectoryCreated $event): void
    {
        $this->webhookService->trigger('directory.created', [
            'directory_id' => $event->directory->id,
            'name' => $event->directory->name,
        ], $event->directory->user_id);
    }

    public function onDirectoryDeleted(DirectoryDeleted $event): void
    {
        $this->webhookService->trigger('directory.deleted', [
            'directory_id' => $event->directory->id,
            'name' => $event->directory->name,
        ], $event->directory->user_id);
    }

    public function onStorageExceeded(StorageExceeded $event): void
    {
        $this->webhookService->trigger('storage.exceeded', [
            'user_id' => $event->user->id,
            'usage' => $event->usage,
            'limit' => $event->limit,
        ], $event->user->id);
    }

    public function onSubscriptionExpired(SubscriptionExpired $event): void
    {
        $this->webhookService->trigger('subscription.expired', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ], $event->user->id);
    }

    public function onFileExpired(FileExpired $event): void
    {
        $this->webhookService->trigger('file.expired', [
            'file_id' => $event->file->id,
            'name' => $event->file->name,
        ], $event->file->user_id);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(FileUploaded::class, [self::class, 'onFileUploaded']);
        $events->listen(FileDownloaded::class, [self::class, 'onFileDownloaded']);
        $events->listen(FileDeleted::class, [self::class, 'onFileDeleted']);
        $events->listen(FileRestored::class, [self::class, 'onFileRestored']);
        $events->listen(DirectoryCreated::class, [self::class, 'onDirectoryCreated']);
        $events->listen(DirectoryDeleted::class, [self::class, 'onDirectoryDeleted']);
        $events->listen(StorageExceeded::class, [self::class, 'onStorageExceeded']);
        $events->listen(SubscriptionExpired::class, [self::class, 'onSubscriptionExpired']);
        $events->listen(FileExpired::class, [self::class, 'onFileExpired']);
    }
}
