<?php

declare(strict_types=1);

namespace App\Contracts\Services\ApiKeys;

use App\Models\Integrations\ApiKey;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ApiKeyServiceInterface
{
    public function createApiKey(string $userId, string $name, ?array $permissions = null, ?int $rateLimit = null): ApiKey;

    public function validateApiKey(string $key): ?User;

    public function revoke(string $userId, string $apiKeyId): bool;

    public function getForUser(string $userId): Collection;

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator;
}
