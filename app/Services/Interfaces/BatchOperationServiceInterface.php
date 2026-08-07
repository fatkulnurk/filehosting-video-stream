<?php

namespace App\Services\Interfaces;

interface BatchOperationServiceInterface
{
    public function storeBatch(array $files, ?string $directoryId, string $userId): array;

    public function downloadZip(array $fileIds, string $userId): string;
}
