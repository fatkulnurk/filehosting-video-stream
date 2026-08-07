<?php

namespace App\Listeners;

use App\Events\DirectoryCreated;
use App\Events\DirectoryDeleted;
use App\Events\FileDeleted;
use App\Events\FileDownloaded;
use App\Events\FileRestored;
use App\Events\FileUploaded;
use App\Services\Interfaces\ActivityLogServiceInterface;
use Illuminate\Events\Dispatcher;

class ActivityLogSubscriber
{
    public function __construct(
        private ActivityLogServiceInterface $logService
    ) {}

    public function onFileUploaded(FileUploaded $event): void
    {
        $this->logService->log('file.upload', $event->file->user_id, \App\Models\File::class, $event->file->id, [
            'name' => $event->file->name,
            'size' => $event->file->size(),
        ]);
    }

    public function onFileDownloaded(FileDownloaded $event): void
    {
        $this->logService->log('file.download', $event->file->user_id, \App\Models\File::class, $event->file->id, [
            'id' => $event->file->id,
        ]);
    }

    public function onFileDeleted(FileDeleted $event): void
    {
        $this->logService->log('file.delete', $event->file->user_id, \App\Models\File::class, $event->file->id, [
            'name' => $event->file->name,
        ]);
    }

    public function onFileRestored(FileRestored $event): void
    {
        $this->logService->log('file.restore', $event->file->user_id, \App\Models\File::class, $event->file->id, [
            'name' => $event->file->name,
        ]);
    }

    public function onDirectoryCreated(DirectoryCreated $event): void
    {
        $this->logService->log('directory.create', $event->directory->user_id, \App\Models\Directory::class, $event->directory->id, [
            'name' => $event->directory->name,
        ]);
    }

    public function onDirectoryDeleted(DirectoryDeleted $event): void
    {
        $this->logService->log('directory.delete', $event->directory->user_id, \App\Models\Directory::class, $event->directory->id, [
            'name' => $event->directory->name,
        ]);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(FileUploaded::class, [self::class, 'onFileUploaded']);
        $events->listen(FileDownloaded::class, [self::class, 'onFileDownloaded']);
        $events->listen(FileDeleted::class, [self::class, 'onFileDeleted']);
        $events->listen(FileRestored::class, [self::class, 'onFileRestored']);
        $events->listen(DirectoryCreated::class, [self::class, 'onDirectoryCreated']);
        $events->listen(DirectoryDeleted::class, [self::class, 'onDirectoryDeleted']);
    }
}
