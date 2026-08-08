<?php

declare(strict_types=1);

namespace App\Services\ActivityLogs;

use App\Contracts\Repositories\ActivityLogs\ActivityLogRepositoryInterface;
use App\Contracts\Services\ActivityLogs\ActivityLogServiceInterface;
use App\Models\ActivityLogs\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ActivityLogService implements ActivityLogServiceInterface
{
    public function __construct(
        private ActivityLogRepositoryInterface $logRepo
    ) {}

    public function log(string $action, ?string $userId = null, ?string $subjectType = null, ?string $subjectId = null, array $properties = []): ActivityLog
    {
        return $this->logRepo->create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function paginate(?string $userId = null, int $perPage = 30): LengthAwarePaginator
    {
        return $this->logRepo->paginate($userId, $perPage);
    }

    public function paginateForUser(string $userId, array $params, ?string $action = null): LengthAwarePaginator
    {
        return $this->logRepo->paginateForUser($userId, $params, $action);
    }

    public function paginateForAdmin(array $params): LengthAwarePaginator
    {
        return $this->logRepo->paginateForAdmin($params);
    }

    public function allActions(): Collection
    {
        return $this->logRepo->allActions();
    }

    public function actionsForUser(string $userId): Collection
    {
        return $this->logRepo->actionsForUser($userId);
    }

    public function cleanupOld(int $days): int
    {
        return $this->logRepo->deleteOlderThan($days);
    }
}
