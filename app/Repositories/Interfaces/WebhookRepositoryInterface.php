<?php

namespace App\Repositories\Interfaces;

use App\Models\Webhook;
use Illuminate\Database\Eloquent\Collection;

interface WebhookRepositoryInterface
{
    public function create(array $data): Webhook;

    public function findById(string $id): ?Webhook;

    public function update(string $id, array $data): Webhook;

    public function delete(string $id): bool;

    public function getForUser(string $userId): Collection;

    public function getActiveForUser(string $userId): Collection;
}
