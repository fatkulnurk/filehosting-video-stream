<?php

declare(strict_types=1);

namespace App\Contracts\Services\Files;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface FileFavoriteServiceInterface
{
    public function toggle(string $userId, string $fileId): bool;

    public function isFavorited(string $userId, string $fileId): bool;

    public function getFavorites(string $userId): Collection;

    public function remove(string $userId, string $fileId): bool;

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator;
}
