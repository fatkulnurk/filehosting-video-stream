<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\ActivityLogs;

use App\Models\ActivityLogs\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ActivityLogRepositoryInterface
{
    public function create(array $data): ActivityLog;

    public function paginate(?string $userId = null, int $perPage = 30): LengthAwarePaginator;

    public function paginateForUser(string $userId, array $params, ?string $action = null): LengthAwarePaginator;

    public function paginateForAdmin(array $params): LengthAwarePaginator;

    public function allActions(): Collection;

    public function actionsForUser(string $userId): Collection;

    public function deleteOlderThan(int $days): int;
}
