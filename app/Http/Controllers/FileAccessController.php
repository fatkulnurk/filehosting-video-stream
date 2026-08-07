<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AppliesTableParams;
use App\Models\FileAccessLog;
use App\Services\Interfaces\FileAccessServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FileAccessController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private FileAccessServiceInterface $accessService
    ) {}

    public function recentlyAccessed(Request $request)
    {
        $params = $this->tableParams($request);

        $files = $this->applyTableQuery(
            FileAccessLog::query()
                ->where('user_id', auth()->id())
                ->with('file.media'),
            $params,
            searchable: [],
            sortable: [
                'name' => \DB::raw('(SELECT f.name FROM files f WHERE f.id = file_access_logs.file_id)'),
                'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = file_access_logs.file_id AND media.model_type = '" . addslashes(\App\Models\File::class) . "')"),
                'views' => \DB::raw('(SELECT f.views FROM files f WHERE f.id = file_access_logs.file_id)'),
                'downloads' => \DB::raw('(SELECT f.downloads FROM files f WHERE f.id = file_access_logs.file_id)'),
                'created_at' => 'created_at',
            ],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Files/RecentlyAccessed', [
            'files' => $files,
            'filters' => $this->tableFilters($params),
        ]);
    }
}