<?php

namespace App\Repositories;

use App\Models\File;
use App\Repositories\Interfaces\FileRepositoryInterface;
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
}
