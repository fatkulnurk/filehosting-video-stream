<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Downloads;

use App\Models\Downloads\DownloadTask;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DownloadTaskRepositoryInterface
{
    public function create(array $data): DownloadTask;

    public function findById(string $id): ?DownloadTask;

    public function update(string $id, array $data): DownloadTask;

    public function getUserTasks(string $userId, int $perPage = 20): LengthAwarePaginator;

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator;

    public function getActiveByType(string $type): Collection;

    public function countActiveByUser(string $userId): int;

    public function deleteOlderThan(CarbonInterface $cutoff): int;
}
