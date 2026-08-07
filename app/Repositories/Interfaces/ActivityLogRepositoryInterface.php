<?php

namespace App\Repositories\Interfaces;

use App\Models\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;

interface ActivityLogRepositoryInterface
{
    public function create(array $data): ActivityLog;

    public function paginate(?string $userId = null, int $perPage = 30): LengthAwarePaginator;

    public function deleteOlderThan(int $days): int;
}
