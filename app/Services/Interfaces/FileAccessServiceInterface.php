<?php

namespace App\Services\Interfaces;

use Illuminate\Support\Collection;

interface FileAccessServiceInterface
{
    public function logAccess(string $fileId, string $action, ?string $userId = null): void;

    public function getRecentlyAccessed(string $userId, int $limit = 10): Collection;

    public function getMostAccessed(int $limit = 10): Collection;
}
