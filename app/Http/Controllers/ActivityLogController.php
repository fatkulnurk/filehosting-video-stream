<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\ActivityLogs\ActivityLogServiceInterface;
use App\Traits\AppliesTableParams;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private ActivityLogServiceInterface $logService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);
        $action = $request->filled('action') ? $request->input('action') : null;

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $this->logService->paginateForUser(auth()->id(), $params, $action),
            'actions' => $this->logService->actionsForUser(auth()->id()),
            'filters' => $this->tableFiltersWith($params, $request, ['action']),
        ]);
    }
}
