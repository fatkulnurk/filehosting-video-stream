<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Directories;

use App\Models\Files\DirectoryShare;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DirectoryShareRepositoryInterface
{
    public function create(array $data): DirectoryShare;

    public function findById(string $id): ?DirectoryShare;

    public function delete(string $id): bool;

    public function getSharedWithMe(string $userId): Collection;

    public function findActiveShare(string $directoryId, string $userId): ?DirectoryShare;

    public function paginateSharedWithMe(string $userId, array $params): LengthAwarePaginator;
}
