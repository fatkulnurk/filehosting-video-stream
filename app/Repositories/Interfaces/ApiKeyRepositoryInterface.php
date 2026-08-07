<?php

namespace App\Repositories\Interfaces;

use App\Models\ApiKey;
use Illuminate\Database\Eloquent\Collection;

interface ApiKeyRepositoryInterface
{
    public function create(array $data): ApiKey;

    public function findById(string $id): ?ApiKey;

    public function findByKey(string $key): ?ApiKey;

    public function getForUser(string $userId): Collection;

    public function update(string $id, array $data): ApiKey;

    public function delete(string $id): bool;
}
