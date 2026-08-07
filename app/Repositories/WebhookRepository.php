<?php

namespace App\Repositories;

use App\Models\Webhook;
use App\Repositories\Interfaces\WebhookRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class WebhookRepository implements WebhookRepositoryInterface
{
    public function create(array $data): Webhook
    {
        return Webhook::create($data);
    }

    public function findById(string $id): ?Webhook
    {
        return Webhook::find($id);
    }

    public function update(string $id, array $data): Webhook
    {
        $webhook = Webhook::findOrFail($id);
        $webhook->update($data);

        return $webhook->fresh();
    }

    public function delete(string $id): bool
    {
        return Webhook::where('id', $id)->delete() > 0;
    }

    public function getForUser(string $userId): Collection
    {
        return Webhook::where('user_id', $userId)->latest()->get();
    }

    public function getActiveForUser(string $userId): Collection
    {
        return Webhook::where('user_id', $userId)->where('is_active', true)->get();
    }
}
