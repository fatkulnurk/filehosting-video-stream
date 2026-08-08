<?php

declare(strict_types=1);

namespace App\Repositories\Files;

use App\Contracts\Repositories\Files\FileFavoriteRepositoryInterface;
use App\Models\Files\File;
use App\Models\Files\FileFavorite;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator
    {
        $sortable = [
            'name' => \DB::raw('(SELECT f.name FROM files f WHERE f.id = file_favorites.file_id)'),
            'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = file_favorites.file_id AND media.model_type = '".addslashes(File::class)."')"),
            'views' => \DB::raw('(SELECT f.views FROM files f WHERE f.id = file_favorites.file_id)'),
            'downloads' => \DB::raw('(SELECT f.downloads FROM files f WHERE f.id = file_favorites.file_id)'),
            'created_at' => 'created_at',
        ];

        $sort = $params['sort'] ?? null;
        $direction = in_array($params['direction'] ?? 'asc', ['asc', 'desc'], true) ? $params['direction'] : 'asc';

        $query = FileFavorite::query()
            ->where('user_id', $userId)
            ->with('file.media');

        if ($sort && isset($sortable[$sort])) {
            $target = $sortable[$sort];
            if ($target instanceof Expression) {
                $query->orderByRaw($target->getValue($query->getQuery()->getGrammar()).' '.$direction);
            } else {
                $query->orderBy($target, $direction);
            }
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }
}
