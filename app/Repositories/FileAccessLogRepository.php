<?php

namespace App\Repositories;

use App\Models\FileAccessLog;
use App\Repositories\Interfaces\FileAccessLogRepositoryInterface;
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
}
