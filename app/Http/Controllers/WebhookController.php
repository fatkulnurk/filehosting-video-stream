<?php

namespace App\Http\Controllers;

use App\Enums\PackageFeature;
use App\Http\Concerns\AppliesTableParams;
use App\Http\Requests\WebhookRequest;
use App\Models\Webhook;
use App\Services\Interfaces\ActivityLogServiceInterface;
use App\Services\Interfaces\WebhookServiceInterface;
use Illuminate\Http\Request;
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
        $this->webhookService->update($id, [
            'url' => $request->url,
            'events' => $request->events,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('webhooks.index')->with('success', 'Webhook updated');
    }

    public function destroy(string $id)
    {
        $this->webhookService->delete($id);

        return redirect()->route('webhooks.index')->with('success', 'Webhook deleted');
    }

    public function regenerate(string $id)
    {
        $webhook = Webhook::where('user_id', auth()->id())->findOrFail($id);

        $secret = $this->webhookService->regenerateSecret($webhook);

        return redirect()->route('webhooks.index')->with('webhookSecret', $secret)->with('success', 'Secret regenerated');
    }

    public function logs(Request $request, string $id)
    {
        $params = $this->tableParams($request);

        $webhook = Webhook::where('user_id', auth()->id())->findOrFail($id);

        $logs = $this->applyTableQuery(
            \App\Models\WebhookLog::query()->where('webhook_id', $id),
            $params,
            searchable: ['event'],
            sortable: [
                'event' => 'event',
                'status' => 'success',
                'response' => 'response_code',
                'duration' => 'duration_ms',
                'created_at' => 'created_at',
            ],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Webhooks/Logs', [
            'webhook' => $webhook,
            'logs' => $logs,
            'filters' => $this->tableFilters($params),
        ]);
    }
}