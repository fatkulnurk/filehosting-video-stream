<?php

declare(strict_types=1);

namespace App\Contracts\Services\Files;

use App\Models\Files\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface FileMigrationServiceInterface
{
    public function moveFile(File $file, string $targetDisk): Media;

    public function copyFile(File $file, string $targetDisk): Media;
}
