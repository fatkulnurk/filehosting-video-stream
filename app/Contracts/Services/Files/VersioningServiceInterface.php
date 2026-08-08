<?php

declare(strict_types=1);

namespace App\Contracts\Services\Files;

use App\Models\Files\File;
use App\Models\Files\FileVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface VersioningServiceInterface
{
    public function createVersion(File $file, UploadedFile $uploadedFile, string $userId, ?string $changelog = null): FileVersion;

    public function restore(File $file, string $versionId): FileVersion;

    public function getVersions(File $file): Collection;

    public function delete(File $file, string $versionId): bool;
}
