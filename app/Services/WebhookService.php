<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\Webhook;
use App\Repositories\Interfaces\WebhookRepositoryInterface;
use App\Services\Interfaces\WebhookServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WebhookService implements WebhookServiceInterface
{
    public function __construct(
        private WebhookRepositoryInterface $webhookRepo
    ) {}

    public function create(array $data): Webhook
    {
        $plainSecret = $data['secret'] ?? Str::random(32);

        $webhook = $this->webhookRepo->create([
            'user_id' => $data['user_id'],
            'url' => $data['url'],
            'secret_hash' => Hash::make($plainSecret),
            'events' => $data['events'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $webhook->plain_secret = $plainSecret;

        return $webhook;
    }

    public function update(string $id, array $data): Webhook
    {
        return $this->webhookRepo->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->webhookRepo->delete($id);
    }

    public function getForUser(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->webhookRepo->getForUser($userId);
    }

    public function verifySignature(Webhook $webhook, string $payload, string $signature): bool
    {
        if (! $webhook->secret_hash) {
            return true;
        }

        $expected = hash_hmac('sha256', $payload, $webhook->secret_hash);

        return hash_equals($expected, $signature);
    }

    public function regenerateSecret(Webhook $webhook): string
    {
        $plainSecret = Str::random(32);

        $this->webhookRepo->update($webhook->id, [
            'secret_hash' => Hash::make($plainSecret),
        ]);

        return $plainSecret;
    }

    public function trigger(string $event, array $payload, ?string $userId = null): void
    {
        $webhooks = $this->webhookRepo->getActiveForUser($userId ?? '');

        if (! $userId) {
            $webhooks = \App\Models\Webhook::where('is_active', true)->get();
        }

        foreach ($webhooks as $webhook) {
            if ($webhook->subscribesTo($event)) {
                SendWebhookJob::dispatch($webhook, $event, $payload);
            }
        }
    }
}
