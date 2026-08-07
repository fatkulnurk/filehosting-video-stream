<?php

namespace App\Repositories;

use App\Models\FileVersion;
use App\Repositories\Interfaces\FileVersionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class FileVersionRepository implements FileVersionRepositoryInterface
{
    public function create(array $data): FileVersion
    {
        return FileVersion::create($data);
    }

    public function getForFile(string $fileId): Collection
    {
        return FileVersion::where('file_id', $fileId)
            ->with('uploader')
            ->orderByDesc('version')
            ->get();
    }

    public function getLatestVersion(string $fileId): ?FileVersion
    {
        return FileVersion::where('file_id', $fileId)
            ->orderByDesc('version')
            ->first();
    }

    public function findById(string $id): ?FileVersion
    {
        return FileVersion::find($id);
    }

    public function delete(string $id): bool
    {
        return FileVersion::where('id', $id)->delete() > 0;
    }
}
