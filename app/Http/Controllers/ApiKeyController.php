<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\ActivityLogs\ActivityLogServiceInterface;
use App\Contracts\Services\ApiKeys\ApiKeyServiceInterface;
use App\Http\Requests\ApiKeys\ApiKeyRequest;
use App\Traits\AppliesTableParams;
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

        return Inertia::render('ApiKeys/Index', [
            'apiKeys' => $this->apiKeyService->paginateForUser(auth()->id(), $params),
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
