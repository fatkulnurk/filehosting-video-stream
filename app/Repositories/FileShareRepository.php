<?php

namespace App\Repositories;

use App\Models\FileShare;
use App\Repositories\Interfaces\FileShareRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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
}
