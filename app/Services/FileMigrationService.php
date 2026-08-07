<?php

namespace App\Services;

use App\Models\File;
use App\Services\Interfaces\FileMigrationServiceInterface;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FileMigrationService implements FileMigrationServiceInterface
{
    public function moveFile(File $file, string $targetDisk): Media
    {
        $media = $file->getFirstMedia();

        if (! $media) {
            throw new \Exception('File has no media');
        }

        $oldChecksum = $media->getCustomProperty('checksum');

        $newMedia = $media->move($targetDisk, $media->getPathRelativeToRoot());

        $newPath = $newMedia->getPath();

        if ($oldChecksum && file_exists($newPath)) {
            $newChecksum = hash_file('sha256', $newPath);

            if ($oldChecksum !== $newChecksum) {
                $newMedia->move($media->disk, $media->getPathRelativeToRoot());

                throw new \Exception('Checksum mismatch after move');
            }
        }

        $newMedia->updateCustomProperties([
            'checksum' => $oldChecksum,
            'migrated_at' => now()->toDateTimeString(),
            'migrated_from' => $media->disk,
        ]);

        // Update checksum record disk/path if exists
        if ($file->file_checksum_id) {
            \App\Models\FileChecksum::where('id', $file->file_checksum_id)->update([
                'disk' => $targetDisk,
                'path' => $newMedia->getPathRelativeToRoot(),
            ]);
        }

        return $newMedia;
    }

    public function copyFile(File $file, string $targetDisk): Media
    {
        $media = $file->getFirstMedia();

        if (! $media) {
            throw new \Exception('File has no media');
        }

        $oldChecksum = $media->getCustomProperty('checksum');

        $newMedia = $media->copy($targetDisk, $media->getPathRelativeToRoot());

        $newMedia->updateCustomProperties([
            'checksum' => $oldChecksum,
            'copied_at' => now()->toDateTimeString(),
            'copied_from' => $media->disk,
        ]);

        return $newMedia;
    }
}
