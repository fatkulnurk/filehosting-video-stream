<?php

declare(strict_types=1);

namespace App\Contracts\Services\Webhooks;

use App\Models\Integrations\Webhook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface WebhookServiceInterface
{
    public function create(array $data): Webhook;

    public function update(string $id, array $data): Webhook;

    public function delete(string $id): bool;

    public function getForUser(string $userId): Collection;

    public function getOwnedWebhook(string $userId, string $id): Webhook;

    public function paginateLogs(string $webhookId, array $params): LengthAwarePaginator;

    public function verifySignature(Webhook $webhook, string $payload, string $signature): bool;

    public function regenerateSecret(Webhook $webhook): string;

    public function trigger(string $event, array $payload, ?string $userId = null): void;
}
