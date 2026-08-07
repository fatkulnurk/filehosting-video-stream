<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Repositories\Interfaces\ActivityLogRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityLogRepository implements ActivityLogRepositoryInterface
{
    public function create(array $data): ActivityLog
    {
        return ActivityLog::create($data);
    }

    public function paginate(?string $userId = null, int $perPage = 30): LengthAwarePaginator
    {
        return ActivityLog::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->with('user')
            ->latest()
            ->paginate($perPage);
    }

    public function deleteOlderThan(int $days): int
    {
        return ActivityLog::where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}
