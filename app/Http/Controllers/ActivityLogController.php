<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\ActivityLogs\ActivityLogServiceInterface;
use App\Http\Requests\ActivityLogs\ActivityLogIndexRequest;
use App\Traits\AppliesTableParams;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private ActivityLogServiceInterface $logService
    ) {}

    public function index(ActivityLogIndexRequest $request)
    {
        $params = $this->tableParams($request);
        $params['sort'] = $request->getSorts();
        $params['filters'] = $request->getFilters();
        $action = $params['filters']['action'] ?? ($request->input('action') ?: null);

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $this->logService->paginateForUser(auth()->id(), $params, $action),
            'actions' => $this->logService->actionsForUser(auth()->id()),
            'filters' => $this->tableFiltersWith($params, $request, ['action']),
        ]);
    }
}
