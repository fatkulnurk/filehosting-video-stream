<?php

declare(strict_types=1);

namespace App\Repositories\Files;

use App\Contracts\Repositories\Files\FileShareRepositoryInterface;
use App\Models\Files\File;
use App\Models\Files\FileShare;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FileShareRepository implements FileShareRepositoryInterface
{
    public function create(array $data): FileShare
    {
        return FileShare::create($data);
    }

    public function findById(string $id): ?FileShare
    {
        return FileShare::find($id);
    }

    public function delete(string $id): bool
    {
        return FileShare::where('id', $id)->delete() > 0;
    }

    public function getSharedWithMe(string $userId): Collection
    {
        return FileShare::where('shared_with', $userId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('file.media')
            ->get();
    }

    public function getSharedByMe(string $userId): Collection
    {
        return FileShare::where('shared_by', $userId)
            ->with('file.media', 'sharedWithUser')
            ->get();
    }

    public function findActiveShare(string $fileId, string $userId): ?FileShare
    {
        return FileShare::where('file_id', $fileId)
            ->where('shared_with', $userId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function paginateSharedWithMe(string $userId, array $params): LengthAwarePaginator
    {
        $sortable = [
            'file' => \DB::raw('(SELECT f.name FROM files f WHERE f.id = file_shares.file_id)'),
            'shared_by' => \DB::raw('(SELECT u.name FROM users u WHERE u.id = file_shares.shared_by)'),
            'permission' => 'permission',
            'expires' => 'expires_at',
            'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = file_shares.file_id AND media.model_type = '".addslashes(File::class)."')"),
            'created_at' => 'created_at',
        ];

        return $this->paginateShares(
            FileShare::query()
                ->where('shared_with', $userId)
                ->with('file.media', 'sharedWithUser', 'sharedByUser'),
            $params,
            $sortable
        );
    }

    public function paginateSharedByMe(string $userId, array $params): LengthAwarePaginator
    {
        $sortable = [
            'file' => \DB::raw('(SELECT f.name FROM files f WHERE f.id = file_shares.file_id)'),
            'shared_with' => \DB::raw('(SELECT u.name FROM users u WHERE u.id = file_shares.shared_with)'),
            'permission' => 'permission',
            'expires' => 'expires_at',
            'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = file_shares.file_id AND media.model_type = '".addslashes(File::class)."')"),
            'created_at' => 'created_at',
        ];

        return $this->paginateShares(
            FileShare::query()
                ->where('shared_by', $userId)
                ->with('file.media', 'sharedWithUser', 'sharedByUser'),
            $params,
            $sortable
        );
    }

    private function paginateShares($query, array $params, array $sortable): LengthAwarePaginator
    {
        $sorts = $params['sort'] ?? [];
        $permission = $params['filters']['permission'] ?? null;

        if ($permission && $permission !== '' && $permission !== 'all') {
            $query->where('permission', $permission);
        }

        if ($sorts) {
            foreach ($sorts as $field => $direction) {
                if (! isset($sortable[$field])) {
                    continue;
                }

                $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
                $target = $sortable[$field];

                if ($target instanceof Expression) {
                    $query->orderByRaw($target->getValue($query->getQuery()->getGrammar()).' '.$direction);
                } else {
                    $query->orderBy($target, $direction);
                }
            }
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }
}
