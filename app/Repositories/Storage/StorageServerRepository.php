<?php

declare(strict_types=1);

namespace App\Repositories\Storage;

use App\Contracts\Repositories\Storage\StorageServerRepositoryInterface;
use App\Models\Storage\StorageServer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class StorageServerRepository implements StorageServerRepositoryInterface
{
    public function create(array $data): StorageServer
    {
        return StorageServer::create($data);
    }

    public function findById(string $id): ?StorageServer
    {
        return StorageServer::find($id);
    }

    public function getUserStorageServers(string $userId): Collection
    {
        return StorageServer::where('user_id', $userId)
            ->orderByDesc('is_primary')
            ->get();
    }

    public function getActiveServers(): Collection
    {
        return StorageServer::where('is_active', true)->get();
    }

    public function update(string $id, array $data): StorageServer
    {
        $server = StorageServer::findOrFail($id);
        $server->update($data);

        return $server->fresh();
    }

    public function delete(string $id): bool
    {
        return StorageServer::where('id', $id)->delete() > 0;
    }

    public function getStats(string $userId): array
    {
        return StorageServer::where('user_id', $userId)
            ->get()
            ->map(fn ($server) => [
                'id' => $server->id,
                'name' => $server->name,
                'driver' => $server->driver,
                'driver_label' => $server->driverLabel(),
                'used' => $server->storage_used,
                'limit' => $server->storage_limit,
                'is_primary' => $server->is_primary,
                'is_active' => $server->is_active,
            ])
            ->toArray();
    }

    public function getPrimary(string $userId): ?StorageServer
    {
        return StorageServer::where('user_id', $userId)->where('is_primary', true)->first();
    }

    public function getLeastUtilizedActive(): ?StorageServer
    {
        return StorageServer::where('is_active', true)
            ->get()
            ->sortBy(fn (StorageServer $server) => $this->utilizationRatio($server))
            ->first();
    }

    private function utilizationRatio(StorageServer $server): float
    {
        if (is_null($server->storage_limit) || $server->storage_limit <= 0) {
            return -1;
        }

        return $server->storage_used / $server->storage_limit;
    }

    public function getGlobalStats(): array
    {
        return StorageServer::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($server) => [
                'id' => $server->id,
                'name' => $server->name,
                'driver' => $server->driver,
                'driver_label' => $server->driverLabel(),
                'used' => $server->storage_used,
                'limit' => $server->storage_limit,
                'is_primary' => $server->is_primary,
                'is_active' => $server->is_active,
            ])
            ->toArray();
    }

    public function findByName(string $name): ?StorageServer
    {
        return StorageServer::where('name', $name)->first();
    }

    public function getCountForUser(string $userId): int
    {
        return StorageServer::where('user_id', $userId)->count();
    }

    public function paginateServers(array $params): LengthAwarePaginator
    {
        $sortable = [
            'name' => 'name',
            'driver' => 'driver',
            'storage_used' => 'storage_used',
            'created_at' => 'created_at',
        ];

        $sort = $params['sort'] ?? null;
        $direction = in_array($params['direction'] ?? 'asc', ['asc', 'desc'], true) ? $params['direction'] : 'asc';
        $filters = $params['filters'] ?? [];

        $query = StorageServer::query();

        $driver = $filters['driver'] ?? null;
        if ($driver && $driver !== '' && $driver !== 'all') {
            $query->where('driver', $driver);
        }

        $status = $filters['status'] ?? null;
        if ($status && $status !== 'all') {
            match ($status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                default => null,
            };
        }

        if (($params['search'] ?? '') !== '') {
            $query->where('name', 'LIKE', '%'.$params['search'].'%');
        }

        if ($sort && isset($sortable[$sort])) {
            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }

    public function getBackupOptions(): Collection
    {
        return StorageServer::where('is_active', true)
            ->where('is_primary', false)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getLatest(int $limit = 10): Collection
    {
        return StorageServer::latest()->limit($limit)->get();
    }
}
