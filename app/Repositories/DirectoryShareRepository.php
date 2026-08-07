<?php

namespace App\Repositories;

use App\Models\DirectoryShare;
use App\Repositories\Interfaces\DirectoryShareRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DirectoryShareRepository implements DirectoryShareRepositoryInterface
{
    public function create(array $data): DirectoryShare
    {
        return DirectoryShare::create($data);
    }

    public function findById(string $id): ?DirectoryShare
    {
        return DirectoryShare::find($id);
    }

    public function delete(string $id): bool
    {
        return DirectoryShare::where('id', $id)->delete() > 0;
    }

    public function getSharedWithMe(string $userId): Collection
    {
        return DirectoryShare::where('shared_with', $userId)
            ->with(['directory.files', 'directory.children'])
            ->get();
    }

    public function findActiveShare(string $directoryId, string $userId): ?DirectoryShare
    {
        return DirectoryShare::where('directory_id', $directoryId)
            ->where('shared_with', $userId)
            ->first();
    }
}
