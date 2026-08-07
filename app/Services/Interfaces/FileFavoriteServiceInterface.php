<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface FileFavoriteServiceInterface
{
    public function toggle(string $userId, string $fileId): bool;

    public function isFavorited(string $userId, string $fileId): bool;

    public function getFavorites(string $userId): Collection;

    public function remove(string $userId, string $fileId): bool;
}
