<?php

declare(strict_types=1);

namespace App\Repositories\Downloads;

use App\Contracts\Repositories\Downloads\DownloadTaskRepositoryInterface;
use App\Models\Downloads\DownloadTask;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DownloadTaskRepository implements DownloadTaskRepositoryInterface
{
    public function create(array $data): DownloadTask
    {
        return DownloadTask::create($data);
    }

    public function findById(string $id): ?DownloadTask
    {
        return DownloadTask::with(['file', 'torrent', 'directory'])->find($id);
    }

    public function update(string $id, array $data): DownloadTask
    {
        $task = DownloadTask::findOrFail($id);
        $task->update($data);

        return $task->fresh();
    }

    public function getUserTasks(string $userId, int $perPage = 20): LengthAwarePaginator
    {
        return DownloadTask::with(['file', 'torrent', 'directory'])
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator
    {
        $sortable = ['type', 'status', 'progress', 'created_at'];
        $sort = $params['sort'] ?? null;

        return DownloadTask::with(['file', 'torrent', 'directory'])
            ->where('user_id', $userId)
            ->when(($params['search'] ?? '') !== '', fn ($q) => $q->where('source', 'LIKE', '%'.$params['search'].'%'))
            ->when($sort && in_array($sort, $sortable, true), fn ($q) => $q->orderBy($sort, $params['direction'] ?? 'desc'), fn ($q) => $q->latest())
            ->paginate($params['per_page'])
            ->withQueryString();
    }

    public function getActiveByType(string $type): Collection
    {
        return DownloadTask::where('type', $type)
            ->whereIn('status', ['queued', 'processing', 'downloading'])
            ->get();
    }

    public function countActiveByUser(string $userId): int
    {
        return DownloadTask::where('user_id', $userId)
            ->whereIn('status', ['queued', 'processing', 'downloading'])
            ->count();
    }

    public function deleteOlderThan(CarbonInterface $cutoff): int
    {
        return DownloadTask::whereIn('status', ['completed', 'failed', 'cancelled'])
            ->where('created_at', '<', $cutoff)
            ->delete();
    }
}
