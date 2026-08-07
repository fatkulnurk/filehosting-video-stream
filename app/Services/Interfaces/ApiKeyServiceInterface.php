<?php

namespace App\Services\Interfaces;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ApiKeyServiceInterface
{
    public function createApiKey(string $userId, string $name, ?array $permissions = null, ?int $rateLimit = null): ApiKey;

    public function validateApiKey(string $key): ?User;

    public function revoke(string $userId, string $apiKeyId): bool;

    public function getForUser(string $userId): Collection;
}
