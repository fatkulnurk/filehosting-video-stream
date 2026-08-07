<?php

namespace App\Services;

use App\Models\File;
use App\Services\Interfaces\BatchOperationServiceInterface;
use App\Services\Interfaces\FileServiceInterface;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BatchOperationService implements BatchOperationServiceInterface
{
    public function __construct(
        private FileServiceInterface $fileService
    ) {}

    public function storeBatch(array $files, ?string $directoryId, string $userId): array
    {
        $results = [];

        foreach ($files as $file) {
            try {
                $fileModel = $this->fileService->store($file, $directoryId, $userId);
                $results[] = ['success' => true, 'file' => $fileModel->id, 'name' => $fileModel->name];
            } catch (\Throwable $e) {
                $results[] = ['success' => false, 'error' => $e->getMessage(), 'name' => $file->getClientOriginalName()];
            }
        }

        return $results;
    }

    public function downloadZip(array $fileIds, string $userId): string
    {
        $files = File::whereIn('id', $fileIds)
            ->where('user_id', $userId)
            ->with('media')
            ->get();

        $zipPath = storage_path('app/batch-'.time().'.zip');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $file) {
            $media = $file->getFirstMedia();

            if (! $media) {
                continue;
            }

            $localPath = null;

            if ($media->disk === 'local' || str_contains($media->disk, 'local')) {
                $localPath = $media->getPath();
            } else {
                // Download remote file temporarily
                $localPath = tempnam(sys_get_temp_dir(), 'fhv_');
                $remoteStream = Storage::disk($media->disk)->readStream($media->getPathRelativeToRoot());

                if ($remoteStream) {
                    file_put_contents($localPath, stream_get_contents($remoteStream));
                    fclose($remoteStream);
                }
            }

            if ($localPath && file_exists($localPath)) {
                $zip->addFile($localPath, $media->file_name);
            }

            if ($media->disk !== 'local' && $localPath && file_exists($localPath)) {
                unlink($localPath);
            }
        }

        $zip->close();

        return $zipPath;
    }
}
