<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\ActivityLogs\ActivityLogServiceInterface;
use App\Http\Controllers\Controller;
use App\Traits\AppliesTableParams;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminLogController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private ActivityLogServiceInterface $logService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);
        $params['filters'] = $request->only(['action']);

        $logs = $this->logService->paginateForAdmin($params);

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'actions' => $this->logService->allActions(),
            'filters' => $this->tableFiltersWith($params, $request, ['action']),
        ]);
    }
}
