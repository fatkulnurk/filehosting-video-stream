<?php

namespace App\Repositories;

use App\Models\FileFavorite;
use App\Repositories\Interfaces\FileFavoriteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class FileFavoriteRepository implements FileFavoriteRepositoryInterface
{
    public function create(array $data): FileFavorite
    {
        return FileFavorite::create($data);
    }

    public function find(string $userId, string $fileId): ?FileFavorite
    {
        return FileFavorite::where('user_id', $userId)
            ->where('file_id', $fileId)
            ->first();
    }

    public function delete(string $userId, string $fileId): bool
    {
        return FileFavorite::where('user_id', $userId)
            ->where('file_id', $fileId)
            ->delete() > 0;
    }

    public function getForUser(string $userId): Collection
    {
        return FileFavorite::where('user_id', $userId)
            ->with('file.media')
            ->latest()
            ->get();
    }

    public function isFavorited(string $userId, string $fileId): bool
    {
        return FileFavorite::where('user_id', $userId)
            ->where('file_id', $fileId)
            ->exists();
    }
}
