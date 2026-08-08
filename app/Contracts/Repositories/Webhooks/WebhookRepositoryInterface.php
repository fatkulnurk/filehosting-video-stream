<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Webhooks;

use App\Models\Integrations\Webhook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface WebhookRepositoryInterface
{
    public function create(array $data): Webhook;

    public function findById(string $id): ?Webhook;

    public function findOwned(string $userId, string $id): ?Webhook;

    public function update(string $id, array $data): Webhook;

    public function delete(string $id): bool;

    public function getForUser(string $userId): Collection;

    public function getActiveForUser(string $userId): Collection;

    public function paginateLogs(string $webhookId, array $params): LengthAwarePaginator;
}
