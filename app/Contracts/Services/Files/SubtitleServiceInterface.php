<?php

declare(strict_types=1);

namespace App\Contracts\Services\Files;

use App\Models\Files\File;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

interface SubtitleServiceInterface
{
    public function store(File $file, UploadedFile $uploadedFile, string $userId, string $language, string $label, bool $isDefault): Media;

    public function delete(File $file, string $mediaId): bool;

    public function getTracks(File $file): array;

    public function serve(File $file, string $mediaId): Response;
}
