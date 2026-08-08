<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Contracts\Repositories\Files\FileFavoriteRepositoryInterface;
use App\Contracts\Services\Files\FileFavoriteServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FileFavoriteService implements FileFavoriteServiceInterface
{
    public function __construct(
        private FileFavoriteRepositoryInterface $favoriteRepo
    ) {}

    public function toggle(string $userId, string $fileId): bool
    {
        $existing = $this->favoriteRepo->find($userId, $fileId);

        if ($existing) {
            $this->favoriteRepo->delete($userId, $fileId);

            return false;
        }

        $this->favoriteRepo->create([
            'user_id' => $userId,
            'file_id' => $fileId,
        ]);

        return true;
    }

    public function isFavorited(string $userId, string $fileId): bool
    {
        return $this->favoriteRepo->isFavorited($userId, $fileId);
    }

    public function getFavorites(string $userId): Collection
    {
        return $this->favoriteRepo->getForUser($userId);
    }

    public function remove(string $userId, string $fileId): bool
    {
        return $this->favoriteRepo->delete($userId, $fileId);
    }

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator
    {
        return $this->favoriteRepo->paginateForUser($userId, $params);
    }
}
