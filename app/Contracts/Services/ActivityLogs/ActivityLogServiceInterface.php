<?php

declare(strict_types=1);

namespace App\Contracts\Services\ActivityLogs;

use App\Models\ActivityLogs\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ActivityLogServiceInterface
{
    public function log(string $action, ?string $userId = null, ?string $subjectType = null, ?string $subjectId = null, array $properties = []): ActivityLog;

    public function paginate(?string $userId = null, int $perPage = 30): LengthAwarePaginator;

    public function paginateForUser(string $userId, array $params, ?string $action = null): LengthAwarePaginator;

    public function paginateForAdmin(array $params): LengthAwarePaginator;

    public function allActions(): Collection;

    public function actionsForUser(string $userId): Collection;

    public function cleanupOld(int $days): int;
}
