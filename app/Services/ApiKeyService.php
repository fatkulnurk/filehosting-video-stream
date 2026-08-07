<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\User;
use App\Repositories\Interfaces\ApiKeyRepositoryInterface;
use App\Services\Interfaces\ApiKeyServiceInterface;

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

    public function getForUser(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->apiKeyRepo->getForUser($userId);
    }
}
