<?php

namespace App\Services\Interfaces;

use App\Models\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface FileMigrationServiceInterface
{
    public function moveFile(File $file, string $targetDisk): Media;

    public function copyFile(File $file, string $targetDisk): Media;
}
