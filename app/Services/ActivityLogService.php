<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Repositories\Interfaces\ActivityLogRepositoryInterface;
use App\Services\Interfaces\ActivityLogServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function cleanupOld(int $days): int
    {
        return $this->logRepo->deleteOlderThan($days);
    }
}
