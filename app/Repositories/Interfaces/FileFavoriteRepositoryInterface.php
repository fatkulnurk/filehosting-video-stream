<?php

namespace App\Repositories\Interfaces;

use App\Models\FileFavorite;
use Illuminate\Database\Eloquent\Collection;

interface FileFavoriteRepositoryInterface
{
    public function create(array $data): FileFavorite;

    public function find(string $userId, string $fileId): ?FileFavorite;

    public function delete(string $userId, string $fileId): bool;

    public function getForUser(string $userId): Collection;

    public function isFavorited(string $userId, string $fileId): bool;
}
