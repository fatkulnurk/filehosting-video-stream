<?php

namespace App\Repositories\Interfaces;

use App\Models\FileAccessLog;
use Illuminate\Support\Collection;

interface FileAccessLogRepositoryInterface
{
    public function create(array $data): FileAccessLog;

    public function getRecentlyAccessed(string $userId, int $limit = 10): Collection;

    public function getMostAccessed(int $limit = 10): Collection;
}
