<?php

declare(strict_types=1);

namespace App\Repositories\Files;

use App\Contracts\Repositories\Files\FileAccessLogRepositoryInterface;
use App\Models\Files\File;
use App\Models\Files\FileAccessLog;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FileAccessLogRepository implements FileAccessLogRepositoryInterface
{
    public function create(array $data): FileAccessLog
    {
        return FileAccessLog::create($data);
    }

    public function getRecentlyAccessed(string $userId, int $limit = 10): Collection
    {
        return FileAccessLog::where('user_id', $userId)
            ->with('file.media')
            ->latest()
            ->limit(200)
            ->get()
            ->unique('file_id')
            ->take($limit)
            ->pluck('file');
    }

    public function getMostAccessed(int $limit = 10): Collection
    {
        return FileAccessLog::selectRaw('file_id, COUNT(*) as access_count')
            ->groupBy('file_id')
            ->orderByDesc('access_count')
            ->with('file.media')
            ->limit($limit)
            ->get()
            ->pluck('file');
    }

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator
    {
        $sortable = [
            'name' => \DB::raw('(SELECT f.name FROM files f WHERE f.id = file_access_logs.file_id)'),
            'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = file_access_logs.file_id AND media.model_type = '".addslashes(File::class)."')"),
            'views' => \DB::raw('(SELECT f.views FROM files f WHERE f.id = file_access_logs.file_id)'),
            'downloads' => \DB::raw('(SELECT f.downloads FROM files f WHERE f.id = file_access_logs.file_id)'),
            'created_at' => 'created_at',
        ];

        $sort = $params['sort'] ?? null;
        $direction = in_array($params['direction'] ?? 'asc', ['asc', 'desc'], true) ? $params['direction'] : 'asc';

        $query = FileAccessLog::query()
            ->where('user_id', $userId)
            ->with('file.media');

        if ($sort && isset($sortable[$sort])) {
            $target = $sortable[$sort];
            if ($target instanceof Expression) {
                $query->orderByRaw($target->getValue($query->getQuery()->getGrammar()).' '.$direction);
            } else {
                $query->orderBy($target, $direction);
            }
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }
}
