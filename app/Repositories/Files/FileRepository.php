<?php

declare(strict_types=1);

namespace App\Repositories\Files;

use App\Contracts\Repositories\Files\FileRepositoryInterface;
use App\Enums\FileStatusFilter;
use App\Enums\FileTypeFilter;
use App\Models\Files\File;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FileRepository implements FileRepositoryInterface
{
    public function create(array $data): File
    {
        return File::create($data);
    }

    public function findById(string $id): ?File
    {
        return File::find($id);
    }

    public function findByIdWithRelations(string $id): ?File
    {
        return File::with(['user.profile', 'directory', 'checksum'])->find($id);
    }

    public function getUserFiles(string $userId, ?string $directoryId = null, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return File::query()
            ->where('user_id', $userId)
            ->when($directoryId, fn ($q) => $q->where('directory_id', $directoryId))
            ->when($directoryId === null, fn ($q) => $q->whereNull('directory_id'))
            ->when($search, fn ($q) => $q->where('name', 'LIKE', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function update(string $id, array $data): File
    {
        $file = File::findOrFail($id);
        $file->update($data);

        return $file->fresh();
    }

    public function delete(string $id): bool
    {
        return File::where('id', $id)->delete() > 0;
    }

    public function forceDelete(string $id): bool
    {
        return File::where('id', $id)->withTrashed()->forceDelete() > 0;
    }

    public function restore(string $id): bool
    {
        return File::where('id', $id)->withTrashed()->restore() > 0;
    }

    public function incrementViews(string $id): void
    {
        File::where('id', $id)->increment('views');
    }

    public function incrementDownloads(string $id): void
    {
        File::where('id', $id)->increment('downloads');
    }

    public function incrementDownloadCount(string $id): void
    {
        File::where('id', $id)->increment('download_count');
    }

    public function search(string $userId, string $query): LengthAwarePaginator
    {
        return File::where('user_id', $userId)
            ->where('name', 'LIKE', "%{$query}%")
            ->latest()
            ->paginate(20);
    }

    public function getUserFilesPaginated(string $userId, ?string $directoryId = null, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return File::query()
            ->where('user_id', $userId)
            ->when($directoryId, fn ($q) => $q->where('directory_id', $directoryId))
            ->when($search, fn ($q) => $q->where('name', 'LIKE', "%{$search}%"))
            ->with('media')
            ->latest()
            ->paginate($perPage);
    }

    public function getUserTrashed(string $userId, int $perPage = 20): LengthAwarePaginator
    {
        return File::onlyTrashed()
            ->with('media')
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getUserStorageUsage(string $userId): int
    {
        return (int) File::where('user_id', $userId)
            ->join('media', function ($join) {
                $join->on('files.id', '=', 'media.model_id')
                    ->where('media.model_type', '=', File::class);
            })
            ->sum('media.size');
    }

    public function countFiles(string $userId): int
    {
        return File::where('user_id', $userId)->count();
    }

    public function recentFiles(string $userId, int $limit = 5): Collection
    {
        return File::where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getWhereIn(array $ids, ?string $userId = null): Collection
    {
        return File::when($userId, fn ($q) => $q->where('user_id', $userId))
            ->whereIn('id', $ids)
            ->get();
    }

    public function getFileCountByDisk(string $disk): int
    {
        return File::query()
            ->join('media', function ($join) use ($disk) {
                $join->on('files.id', '=', 'media.model_id')
                    ->where('media.model_type', '=', File::class)
                    ->where('media.disk', '=', $disk);
            })
            ->count();
    }

    public function paginateForDirectory(string $userId, ?string $directoryId, array $params): LengthAwarePaginator
    {
        $sortable = [
            'name' => 'name',
            'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = files.id AND media.model_type = '".addslashes(File::class)."')"),
            'views' => 'views',
            'downloads' => 'downloads',
            'created_at' => 'created_at',
        ];

        $sorts = $params['sort'] ?? [];

        $query = File::query()
            ->where('user_id', $userId)
            ->when($directoryId, fn ($q) => $q->where('directory_id', $directoryId))
            ->when(! $directoryId, fn ($q) => $q->whereNull('directory_id'))
            ->with('media');

        if (($params['search'] ?? '') !== '') {
            $query->where('name', 'LIKE', '%'.$params['search'].'%');
        }

        if ($sorts) {
            foreach ($sorts as $field => $direction) {
                if (! isset($sortable[$field])) {
                    continue;
                }

                $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
                $this->applySortable($query, $sortable[$field], $direction);
            }
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }

    public function paginateTrashedForUser(string $userId, array $params): LengthAwarePaginator
    {
        $sortable = [
            'name' => 'name',
            'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = files.id AND media.model_type = '".addslashes(File::class)."')"),
            'deleted_at' => 'deleted_at',
        ];

        $sorts = $params['sort'] ?? [];

        $query = File::query()
            ->where('user_id', $userId)
            ->onlyTrashed()
            ->with('media');

        if (($params['search'] ?? '') !== '') {
            $query->where('name', 'LIKE', '%'.$params['search'].'%');
        }

        if ($sorts) {
            foreach ($sorts as $field => $direction) {
                if (! isset($sortable[$field])) {
                    continue;
                }

                $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
                $this->applySortable($query, $sortable[$field], $direction);
            }
        } else {
            $query->orderByDesc('deleted_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }

    public function findTrashedForUser(string $userId, string $id): ?File
    {
        return File::withTrashed()
            ->where('user_id', $userId)
            ->where('id', $id)
            ->first();
    }

    public function getTrashedForUser(string $userId): Collection
    {
        return File::onlyTrashed()
            ->where('user_id', $userId)
            ->get();
    }

    public function paginateAdmin(array $params): LengthAwarePaginator
    {
        $sortable = [
            'name' => 'name',
            'user' => \DB::raw('(SELECT u.name FROM users u WHERE u.id = files.user_id)'),
            'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = files.id AND media.model_type = '".addslashes(File::class)."')"),
            'views' => 'views',
            'downloads' => 'downloads',
            'created_at' => 'created_at',
            'is_public' => 'is_public',
        ];

        $sorts = $params['sort'] ?? [];
        $filters = $params['filters'] ?? [];

        $query = File::query()
            ->with(['user', 'media'])
            ->withTrashed();

        $userId = $filters['user_id'] ?? null;
        if ($userId && $userId !== '') {
            $query->where('user_id', $userId);
        }

        $status = $filters['status'] ?? null;
        if ($status && $status !== 'all') {
            match (FileStatusFilter::tryFrom($status)) {
                FileStatusFilter::Public => $query->where('is_public', true),
                FileStatusFilter::Private => $query->where('is_public', false),
                FileStatusFilter::Trashed => $query->onlyTrashed(),
                FileStatusFilter::Active => $query->whereNull('deleted_at'),
                default => null,
            };
        }

        $type = $filters['type'] ?? null;
        if ($type && $type !== 'all') {
            match (FileTypeFilter::tryFrom($type)) {
                FileTypeFilter::Video => $query->whereHas('media', fn ($m) => $m->where('mime_type', 'LIKE', 'video/%')),
                FileTypeFilter::Image => $query->whereHas('media', fn ($m) => $m->where('mime_type', 'LIKE', 'image/%')),
                FileTypeFilter::Document => $query->whereDoesntHave('media', fn ($m) => $m->where('mime_type', 'LIKE', 'video/%')->orWhere('mime_type', 'LIKE', 'image/%')),
                default => null,
            };
        }

        if (($params['search'] ?? '') !== '') {
            $query->where('name', 'LIKE', '%'.$params['search'].'%');
        }

        if ($sorts) {
            foreach ($sorts as $field => $direction) {
                if (! isset($sortable[$field])) {
                    continue;
                }

                $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
                $this->applySortable($query, $sortable[$field], $direction);
            }
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }

    public function findForAdmin(string $id): ?File
    {
        return File::withTrashed()
            ->with(['user', 'directory', 'checksum', 'versions'])
            ->find($id);
    }

    public function getLatestForUser(string $userId, int $limit = 10): Collection
    {
        return File::withTrashed()
            ->with(['media', 'directory'])
            ->where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function countAllWithTrashed(string $userId): int
    {
        return File::withTrashed()->where('user_id', $userId)->count();
    }

    public function getByDirectoryWithTrashed(string $directoryId): Collection
    {
        return File::withTrashed()
            ->where('directory_id', $directoryId)
            ->get();
    }

    private function applySortable($query, Expression|string $target, string $direction): void
    {
        if ($target instanceof Expression) {
            $query->orderByRaw($target->getValue($query->getQuery()->getGrammar()).' '.$direction);
        } else {
            $query->orderBy($target, $direction);
        }
    }
}
