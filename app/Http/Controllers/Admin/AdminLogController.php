<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\ActivityLogs\ActivityLogServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLogIndexRequest;
use App\Traits\AppliesTableParams;
use Inertia\Inertia;

class AdminLogController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private ActivityLogServiceInterface $logService
    ) {}

    public function index(AdminLogIndexRequest $request)
    {
        $params = $this->tableParams($request);
        $params['sort'] = $request->getSorts();
        $params['filters'] = $request->getFilters();

        $logs = $this->logService->paginateForAdmin($params);

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'actions' => $this->logService->allActions(),
            'filters' => $this->tableFiltersWith($params, $request, ['action']),
        ]);
    }
}
