<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\ApiKeys;

use App\Models\Integrations\ApiKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ApiKeyRepositoryInterface
{
    public function create(array $data): ApiKey;

    public function findById(string $id): ?ApiKey;

    public function findByKey(string $key): ?ApiKey;

    public function getForUser(string $userId): Collection;

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator;

    public function update(string $id, array $data): ApiKey;

    public function delete(string $id): bool;
}
