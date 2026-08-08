<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\ActivityLogs\ActivityLogServiceInterface;
use App\Contracts\Services\Webhooks\WebhookServiceInterface;
use App\Http\Requests\Webhooks\WebhookLogIndexRequest;
use App\Http\Requests\Webhooks\WebhookRequest;
use App\Traits\AppliesTableParams;
use Inertia\Inertia;

class WebhookController extends Controller
{
    use AppliesTableParams;

    protected array $allowedEvents = [
        'file.uploaded',
        'file.downloaded',
        'file.deleted',
        'file.restored',
        'directory.created',
        'directory.deleted',
        'storage.exceeded',
        'subscription.expired',
        'file.expired',
    ];

    public function __construct(
        private WebhookServiceInterface $webhookService,
        private ActivityLogServiceInterface $activityLog
    ) {}

    public function index()
    {
        return Inertia::render('Webhooks/Index', [
            'webhooks' => $this->webhookService->getForUser(auth()->id()),
            'events' => $this->allowedEvents,
        ]);
    }

    public function store(WebhookRequest $request)
    {
        $webhook = $this->webhookService->create([
            'user_id' => auth()->id(),
            'url' => $request->url,
            'events' => $request->events,
            'secret' => $request->secret,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->activityLog->log('webhook.create', auth()->id(), properties: ['url' => $webhook->url]);

        if (isset($webhook->plain_secret)) {
            return redirect()->route('webhooks.index')
                ->with('webhookSecret', $webhook->plain_secret)
                ->with('success', 'Webhook created');
        }

        return redirect()->route('webhooks.index')->with('success', 'Webhook created');
    }

    public function update(WebhookRequest $request, string $id)
    {
        $this->webhookService->getOwnedWebhook(auth()->id(), $id);

        $this->webhookService->update($id, [
            'url' => $request->url,
            'events' => $request->events,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('webhooks.index')->with('success', 'Webhook updated');
    }

    public function destroy(string $id)
    {
        $this->webhookService->getOwnedWebhook(auth()->id(), $id);

        $this->webhookService->delete($id);

        return redirect()->route('webhooks.index')->with('success', 'Webhook deleted');
    }

    public function regenerate(string $id)
    {
        $webhook = $this->webhookService->getOwnedWebhook(auth()->id(), $id);

        $secret = $this->webhookService->regenerateSecret($webhook);

        return redirect()->route('webhooks.index')->with('webhookSecret', $secret)->with('success', 'Secret regenerated');
    }

    public function logs(WebhookLogIndexRequest $request, string $id)
    {
        $params = $this->tableParams($request);
        $params['sort'] = $request->getSorts();

        $webhook = $this->webhookService->getOwnedWebhook(auth()->id(), $id);

        $logs = $this->webhookService->paginateLogs($id, $params);

        return Inertia::render('Webhooks/Logs', [
            'webhook' => $webhook,
            'logs' => $logs,
            'filters' => $this->tableFilters($params),
        ]);
    }
}
