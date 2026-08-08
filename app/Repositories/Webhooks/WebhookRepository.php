<?php

declare(strict_types=1);

namespace App\Repositories\Webhooks;

use App\Contracts\Repositories\Webhooks\WebhookRepositoryInterface;
use App\Models\Integrations\Webhook;
use App\Models\Integrations\WebhookLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function findOwned(string $userId, string $id): ?Webhook
    {
        return Webhook::where('user_id', $userId)
            ->where('id', $id)
            ->first();
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

    public function paginateLogs(string $webhookId, array $params): LengthAwarePaginator
    {
        $sortable = [
            'event' => 'event',
            'status' => 'success',
            'response' => 'response_code',
            'duration' => 'duration_ms',
            'created_at' => 'created_at',
        ];

        $sort = $params['sort'] ?? null;
        $direction = in_array($params['direction'] ?? 'asc', ['asc', 'desc'], true) ? $params['direction'] : 'asc';

        $query = WebhookLog::query()->where('webhook_id', $webhookId);

        if (($params['search'] ?? '') !== '') {
            $query->where('event', 'LIKE', '%'.$params['search'].'%');
        }

        if ($sort && isset($sortable[$sort])) {
            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }
}
