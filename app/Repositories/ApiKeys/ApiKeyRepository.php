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
        $sortable = [
            'name' => 'name',
            'rate_limit' => 'rate_limit',
            'last_used_at' => 'last_used_at',
            'created_at' => 'created_at',
        ];

        $sorts = $params['sort'] ?? [];

        $query = ApiKey::query()
            ->where('user_id', $userId)
            ->when(($params['search'] ?? '') !== '', fn ($q) => $q->where('name', 'LIKE', '%'.$params['search'].'%'));

        $this->applySorts($query, $sorts, $sortable, 'created_at');

        return $query->paginate($params['per_page'])->withQueryString();
    }

    private function applySorts($query, array $sorts, array $sortable, string $defaultColumn): void
    {
        if ($sorts) {
            foreach ($sorts as $field => $direction) {
                if (! isset($sortable[$field])) {
                    continue;
                }

                $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
                $query->orderBy($sortable[$field], $direction);
            }

            return;
        }

        $query->orderByDesc($defaultColumn);
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
