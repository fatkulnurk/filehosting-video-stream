<?php

namespace App\Repositories\Interfaces;

use App\Models\FileShare;
use Illuminate\Database\Eloquent\Collection;

interface FileShareRepositoryInterface
{
    public function create(array $data): FileShare;

    public function findById(string $id): ?FileShare;

    public function delete(string $id): bool;

    public function getSharedWithMe(string $userId): Collection;

    public function getSharedByMe(string $userId): Collection;

    public function findActiveShare(string $fileId, string $userId): ?FileShare;
}
