<?php

namespace App\Repositories;

use App\Models\ApiKey;
use App\Repositories\Interfaces\ApiKeyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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
