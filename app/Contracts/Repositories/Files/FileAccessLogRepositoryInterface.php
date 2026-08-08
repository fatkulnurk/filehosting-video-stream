<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Files;

use App\Models\Files\FileAccessLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FileAccessLogRepositoryInterface
{
    public function create(array $data): FileAccessLog;

    public function getRecentlyAccessed(string $userId, int $limit = 10): Collection;

    public function getMostAccessed(int $limit = 10): Collection;

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator;
}
