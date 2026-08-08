<?php

declare(strict_types=1);

namespace App\Contracts\Services\Files;

interface BatchOperationServiceInterface
{
    public function storeBatch(array $files, ?string $directoryId, string $userId): array;

    public function downloadZip(array $fileIds, string $userId): string;
}
