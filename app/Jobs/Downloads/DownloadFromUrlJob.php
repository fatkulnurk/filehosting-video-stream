<?php

declare(strict_types=1);

namespace App\Jobs\Downloads;

use App\Contracts\Repositories\Downloads\DownloadTaskRepositoryInterface;
use App\Contracts\Services\Downloads\UrlDownloaderInterface;
use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Storage\StorageQuotaServiceInterface;
use App\Enums\DownloadTaskStatus;
use App\Exceptions\Files\StorageQuotaExceededException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DownloadFromUrlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(
        public string $taskId
    ) {}

    public function handle(
        DownloadTaskRepositoryInterface $taskRepository,
        FileServiceInterface $fileService,
        StorageQuotaServiceInterface $quotaService,
        UrlDownloaderInterface $urlDownloader
    ): void {
        $task = $taskRepository->findById($this->taskId);

        if (! $task || $task->type->value !== 'url') {
            return;
        }

        $taskRepository->update($task->id, ['status' => DownloadTaskStatus::Processing]);

        $tmpPath = tempnam(sys_get_temp_dir(), 'fhv_url_');

        if ($tmpPath === false) {
            $taskRepository->update($task->id, ['status' => DownloadTaskStatus::Failed, 'error' => 'Failed to create temp file.']);

            return;
        }

        try {
            $urlDownloader->download($task->source, $tmpPath);

            clearstatcache(true, $tmpPath);
            $size = (int) filesize($tmpPath);

            if (! $quotaService->canUpload($task->user_id, $size)) {
                throw new StorageQuotaExceededException('Storage quota exceeded for URL download.');
            }

            $originalName = $this->guessFileName($task->source);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath) ?: 'application/octet-stream';

            $uploadedFile = new UploadedFile($tmpPath, $originalName, $mime, null, true);

            $file = $fileService->store($uploadedFile, $task->directory_id, $task->user_id, $task->disk);

            $taskRepository->update($task->id, [
                'status' => DownloadTaskStatus::Completed,
                'progress' => 1.0,
                'file_id' => $file->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('URL download task failed.', [
                'task_id' => $task->id,
                'url' => $task->source,
                'error' => $e->getMessage(),
            ]);

            $taskRepository->update($task->id, [
                'status' => DownloadTaskStatus::Failed,
                'error' => $e->getMessage(),
            ]);
        } finally {
            @unlink($tmpPath);
        }
    }

    private function guessFileName(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $base = basename($path);

        if ($base !== '' && $base !== '/') {
            $name = urldecode($base);

            return $name !== '' ? $name : 'file.bin';
        }

        return 'file.bin';
    }
}
