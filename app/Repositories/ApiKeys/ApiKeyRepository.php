<?php

declare(strict_types=1);

namespace App\Repositories\ApiKeys;

use App\Contracts\Repositories\ApiKeys\ApiKeyRepositoryInterface;
use App\Models\Integrations\ApiKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiKeyRepository implements ApiKeyRepositoryInterface
{
    public function create(array $data): ApiKey
    {
        return ApiKey::create($data);
    }

    public function findById(string $id): ?ApiKey
    {
        return ApiKey::find($id);
    }

    public function findByKey(string $key): ?ApiKey
    {
        return ApiKey::where('key', $key)->first();
    }

    public function getForUser(string $userId): Collection
    {
        return ApiKey::where('user_id', $userId)->latest()->get();
    }

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator
    {
        $sortable = ['name', 'rate_limit', 'last_used_at', 'created_at'];
        $sort = $params['sort'] ?? null;

        return ApiKey::query()
            ->where('user_id', $userId)
            ->when(($params['search'] ?? '') !== '', fn ($q) => $q->where('name', 'LIKE', '%'.$params['search'].'%'))
            ->when($sort && in_array($sort, $sortable, true), fn ($q) => $q->orderBy($sort, $params['direction'] ?? 'desc'), fn ($q) => $q->latest())
            ->paginate($params['per_page'])
            ->withQueryString();
    }

    public function update(string $id, array $data): ApiKey
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->update($data);

        return $apiKey->fresh();
    }

    public function delete(string $id): bool
    {
        return ApiKey::where('id', $id)->delete() > 0;
    }
}
