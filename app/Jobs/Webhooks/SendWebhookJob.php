<?php

declare(strict_types=1);

namespace App\Jobs\Webhooks;

use App\Models\Integrations\Webhook;
use App\Models\Integrations\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Webhook $webhook,
        public string $event,
        public array $payload
    ) {}

    public function handle(): void
    {
        $start = microtime(true);

        try {
            $json = json_encode($this->payload);

            $signature = $this->webhook->secret_hash
                ? hash_hmac('sha256', $json, $this->webhook->secret_hash)
                : null;

            $headers = ['X-Webhook-Event' => $this->event];

            if ($signature) {
                $headers['X-Webhook-Signature'] = $signature;
            }

            $response = Http::timeout(15)
                ->withHeaders($headers)
                ->post($this->webhook->url, $this->payload);

            $duration = (int) ((microtime(true) - $start) * 1000);

            WebhookLog::create([
                'webhook_id' => $this->webhook->id,
                'event' => $this->event,
                'payload' => $this->payload,
                'response_code' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 2000),
                'duration_ms' => $duration,
                'success' => $response->successful(),
            ]);

            if ($response->successful()) {
                $this->webhook->update([
                    'last_triggered_at' => now(),
                    'failure_count' => 0,
                ]);
            } else {
                $this->webhook->increment('failure_count');
            }
        } catch (\Throwable $e) {
            $duration = (int) ((microtime(true) - $start) * 1000);

            WebhookLog::create([
                'webhook_id' => $this->webhook->id,
                'event' => $this->event,
                'payload' => $this->payload,
                'response_code' => null,
                'response_body' => mb_substr($e->getMessage(), 0, 2000),
                'duration_ms' => $duration,
                'success' => false,
            ]);

            $this->webhook->increment('failure_count');

            throw $e;
        }
    }
}
