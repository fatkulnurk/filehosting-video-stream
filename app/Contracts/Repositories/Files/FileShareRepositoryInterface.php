<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Files;

use App\Models\Files\FileShare;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface FileShareRepositoryInterface
{
    public function create(array $data): FileShare;

    public function findById(string $id): ?FileShare;

    public function delete(string $id): bool;

    public function getSharedWithMe(string $userId): Collection;

    public function getSharedByMe(string $userId): Collection;

    public function findActiveShare(string $fileId, string $userId): ?FileShare;

    public function paginateSharedWithMe(string $userId, array $params): LengthAwarePaginator;

    public function paginateSharedByMe(string $userId, array $params): LengthAwarePaginator;
}
