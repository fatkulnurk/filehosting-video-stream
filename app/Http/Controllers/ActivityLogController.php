<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AppliesTableParams;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    use AppliesTableParams;

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $logs = $this->applyTableQuery(
            $this->applyFilters(
                ActivityLog::query()->where('user_id', auth()->id()),
                $request,
                ['action' => 'action']
            ),
            $params,
            searchable: ['action'],
            sortable: [
                'action' => 'action',
                'ip' => 'ip_address',
                'description' => 'description',
                'created_at' => 'created_at',
            ],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'actions' => ActivityLog::where('user_id', auth()->id())->distinct('action')->orderBy('action')->pluck('action'),
            'filters' => $this->tableFiltersWith($params, $request, ['action']),
        ]);
    }
}