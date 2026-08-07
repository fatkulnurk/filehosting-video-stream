<?php

namespace App\Services;

use App\Repositories\Interfaces\FileAccessLogRepositoryInterface;
use App\Services\Interfaces\FileAccessServiceInterface;
use Illuminate\Support\Collection;

class FileAccessService implements FileAccessServiceInterface
{
    public function __construct(
        private FileAccessLogRepositoryInterface $logRepo
    ) {}

    public function logAccess(string $fileId, string $action, ?string $userId = null): void
    {
        $this->logRepo->create([
            'user_id' => $userId ?? auth()->id(),
            'file_id' => $fileId,
            'action' => $action,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getRecentlyAccessed(string $userId, int $limit = 10): Collection
    {
        return $this->logRepo->getRecentlyAccessed($userId, $limit);
    }

    public function getMostAccessed(int $limit = 10): Collection
    {
        return $this->logRepo->getMostAccessed($limit);
    }
}
