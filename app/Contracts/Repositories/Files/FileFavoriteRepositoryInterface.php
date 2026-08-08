<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Files;

use App\Models\Files\FileFavorite;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface FileFavoriteRepositoryInterface
{
    public function create(array $data): FileFavorite;

    public function find(string $userId, string $fileId): ?FileFavorite;

    public function delete(string $userId, string $fileId): bool;

    public function getForUser(string $userId): Collection;

    public function isFavorited(string $userId, string $fileId): bool;

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator;
}
