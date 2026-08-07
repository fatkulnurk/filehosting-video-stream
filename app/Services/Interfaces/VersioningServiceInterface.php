<?php

namespace App\Services\Interfaces;

use App\Models\File;
use App\Models\FileVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface VersioningServiceInterface
{
    public function createVersion(File $file, UploadedFile $uploadedFile, string $userId, ?string $changelog = null): FileVersion;

    public function restore(File $file, string $versionId): FileVersion;

    public function getVersions(File $file): Collection;

    public function delete(File $file, string $versionId): bool;
}
