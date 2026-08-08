<?php

declare(strict_types=1);

namespace App\Contracts\Services\Files;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FileAccessServiceInterface
{
    public function logAccess(string $fileId, string $action, ?string $userId = null): void;

    public function getRecentlyAccessed(string $userId, int $limit = 10): Collection;

    public function getMostAccessed(int $limit = 10): Collection;

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator;
}
