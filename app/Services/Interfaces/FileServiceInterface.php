<?php

namespace App\Services\Interfaces;

use App\Models\File;
use App\Models\FileChecksum;
use Illuminate\Http\UploadedFile;

interface FileServiceInterface
{
    public function store(UploadedFile $file, ?string $directoryId, string $userId, ?string $diskName = null): File;

    public function storeFromChecksum(FileChecksum $checksum, ?string $directoryId, string $userId): File;

    public function delete(File $file): bool;

    public function forceDelete(File $file): bool;

    public function restore(File $file): bool;

    public function move(File $file, ?string $newDirectoryId): File;

    public function rename(File $file, string $name): File;

    public function getById(string $id): ?File;

    public function getPublicFile(string $id): ?File;

    public function incrementViews(File $file): void;

    public function incrementDownloads(File $file): void;

    public function updateSettings(File $file, array $data): File;
}
