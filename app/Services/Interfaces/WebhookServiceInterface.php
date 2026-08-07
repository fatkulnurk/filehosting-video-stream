<?php

namespace App\Services\Interfaces;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Collection;

interface WebhookServiceInterface
{
    public function create(array $data): Webhook;

    public function update(string $id, array $data): Webhook;

    public function delete(string $id): bool;

    public function getForUser(string $userId): Collection;

    public function verifySignature(Webhook $webhook, string $payload, string $signature): bool;

    public function regenerateSecret(Webhook $webhook): string;

    public function trigger(string $event, array $payload, ?string $userId = null): void;
}
