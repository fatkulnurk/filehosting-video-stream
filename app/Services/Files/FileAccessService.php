<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Contracts\Repositories\Files\FileAccessLogRepositoryInterface;
use App\Contracts\Services\Files\FileAccessServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator
    {
        return $this->logRepo->paginateForUser($userId, $params);
    }
}
