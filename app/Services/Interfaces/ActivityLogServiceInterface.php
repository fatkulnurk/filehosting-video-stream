<?php

namespace App\Services\Interfaces;

use App\Models\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;

interface ActivityLogServiceInterface
{
    public function log(string $action, ?string $userId = null, ?string $subjectType = null, ?string $subjectId = null, array $properties = []): ActivityLog;

    public function paginate(?string $userId = null, int $perPage = 30): LengthAwarePaginator;

    public function cleanupOld(int $days): int;
}
