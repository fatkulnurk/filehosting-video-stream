<?php

declare(strict_types=1);

namespace App\Repositories\ActivityLogs;

use App\Contracts\Repositories\ActivityLogs\ActivityLogRepositoryInterface;
use App\Models\ActivityLogs\ActivityLog;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

    public function paginateForUser(string $userId, array $params, ?string $action = null): LengthAwarePaginator
    {
        $sortable = [
            'action' => 'action',
            'ip_address' => 'ip_address',
            'description' => 'description',
            'created_at' => 'created_at',
        ];

        $sorts = $params['sort'] ?? [];

        $query = ActivityLog::query()
            ->where('user_id', $userId)
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when(($params['search'] ?? '') !== '', fn ($q) => $q->where('action', 'LIKE', '%'.$params['search'].'%'));

        if ($sorts) {
            foreach ($sorts as $field => $direction) {
                if (! isset($sortable[$field])) {
                    continue;
                }

                $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
                $query->orderBy($sortable[$field], $direction);
            }
        } else {
            $query->latest();
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }

    public function actionsForUser(string $userId): Collection
    {
        return ActivityLog::where('user_id', $userId)
            ->distinct('action')
            ->orderBy('action')
            ->pluck('action');
    }

    public function paginateForAdmin(array $params): LengthAwarePaginator
    {
        $sortable = [
            'user' => \DB::raw('(SELECT u.name FROM users u WHERE u.id = activity_logs.user_id)'),
            'action' => 'action',
            'ip' => 'ip_address',
            'description' => 'description',
            'created_at' => 'created_at',
        ];

        $sorts = $params['sort'] ?? [];
        $action = $params['filters']['action'] ?? null;

        $query = ActivityLog::query()->with('user');

        if ($action && $action !== '' && $action !== 'all') {
            $query->where('action', $action);
        }

        if (($params['search'] ?? '') !== '') {
            $query->where('action', 'LIKE', '%'.$params['search'].'%');
        }

        if ($sorts) {
            foreach ($sorts as $field => $direction) {
                if (! isset($sortable[$field])) {
                    continue;
                }

                $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
                $target = $sortable[$field];

                if ($target instanceof Expression) {
                    $query->orderByRaw($target->getValue($query->getQuery()->getGrammar()).' '.$direction);
                } else {
                    $query->orderBy($target, $direction);
                }
            }
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }

    public function allActions(): Collection
    {
        return ActivityLog::distinct('action')
            ->orderBy('action')
            ->pluck('action');
    }

    public function deleteOlderThan(int $days): int
    {
        return ActivityLog::where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}
