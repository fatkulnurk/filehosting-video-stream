<?php

namespace App\Services\Interfaces;

interface StorageQuotaServiceInterface
{
    public function getUsage(string $userId): int;

    public function canUpload(string $userId, int $fileSize): bool;

    public function getRemaining(string $userId): ?int;

    public function recalculate(string $userId): int;

    public function getLimit(string $userId): ?int;
}
