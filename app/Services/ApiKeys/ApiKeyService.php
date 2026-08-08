<?php

declare(strict_types=1);

namespace App\Services\ApiKeys;

use App\Contracts\Repositories\ApiKeys\ApiKeyRepositoryInterface;
use App\Contracts\Services\ApiKeys\ApiKeyServiceInterface;
use App\Models\Integrations\ApiKey;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiKeyService implements ApiKeyServiceInterface
{
    public function __construct(
        private ApiKeyRepositoryInterface $apiKeyRepo
    ) {}

    public function createApiKey(string $userId, string $name, ?array $permissions = null, ?int $rateLimit = null): ApiKey
    {
        return $this->apiKeyRepo->create([
            'user_id' => $userId,
            'name' => $name,
            'key' => 'sk_'.bin2hex(random_bytes(30)),
            'permissions' => $permissions ?? ['file.read', 'file.write'],
            'rate_limit' => $rateLimit ?? 60,
            'expires_at' => now()->addYear(),
        ]);
    }

    public function validateApiKey(string $key): ?User
    {
        $apiKey = $this->apiKeyRepo->findByKey($key);

        if (! $apiKey || ! $apiKey->is_active) {
            return null;
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return null;
        }

        $this->apiKeyRepo->update($apiKey->id, ['last_used_at' => now()]);

        return $apiKey->user;
    }

    public function revoke(string $userId, string $apiKeyId): bool
    {
        $apiKey = $this->apiKeyRepo->findById($apiKeyId);

        if (! $apiKey || $apiKey->user_id !== $userId) {
            return false;
        }

        return $this->apiKeyRepo->delete($apiKeyId);
    }

    public function getForUser(string $userId): Collection
    {
        return $this->apiKeyRepo->getForUser($userId);
    }

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator
    {
        return $this->apiKeyRepo->paginateForUser($userId, $params);
    }
}
