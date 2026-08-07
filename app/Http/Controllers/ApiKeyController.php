<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AppliesTableParams;
use App\Http\Requests\ApiKeyRequest;
use App\Models\ApiKey;
use App\Services\Interfaces\ActivityLogServiceInterface;
use App\Services\Interfaces\ApiKeyServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiKeyController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private ApiKeyServiceInterface $apiKeyService,
        private ActivityLogServiceInterface $activityLog
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $apiKeys = $this->applyTableQuery(
            ApiKey::query()->where('user_id', auth()->id()),
            $params,
            searchable: ['name'],
            sortable: [
                'name' => 'name',
                'rate' => 'rate_limit',
                'last_used' => 'last_used_at',
                'created_at' => 'created_at',
            ],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('ApiKeys/Index', [
            'apiKeys' => $apiKeys,
            'filters' => $this->tableFilters($params),
        ]);
    }

    public function store(ApiKeyRequest $request)
    {
        $apiKey = $this->apiKeyService->createApiKey(
            auth()->id(),
            $request->name,
            $request->permissions,
            $request->rate_limit
        );

        $this->activityLog->log('api_key.create', auth()->id(), properties: ['name' => $apiKey->name]);

        return redirect()->route('api-keys.index')->with('newApiKey', $apiKey->key);
    }

    public function destroy(string $id)
    {
        $this->apiKeyService->revoke(auth()->id(), $id);

        return redirect()->route('api-keys.index')->with('success', 'API key revoked');
    }
}