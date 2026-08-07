<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Concerns\AppliesTableParams;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminLogController extends Controller
{
    use AppliesTableParams;

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $logs = $this->applyTableQuery(
            $this->applyFilters(
                ActivityLog::query()->with('user'),
                $request,
                ['action' => 'action']
            ),
            $params,
            searchable: ['action'],
            sortable: [
                'user' => \DB::raw('(SELECT u.name FROM users u WHERE u.id = activity_logs.user_id)'),
                'action' => 'action',
                'ip' => 'ip_address',
                'description' => 'description',
                'created_at' => 'created_at',
            ],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'actions' => ActivityLog::distinct('action')->orderBy('action')->pluck('action'),
            'filters' => $this->tableFiltersWith($params, $request, ['action']),
        ]);
    }
}