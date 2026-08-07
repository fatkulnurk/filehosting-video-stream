<?php

namespace App\Repositories\Interfaces;

use App\Models\DirectoryShare;
use Illuminate\Database\Eloquent\Collection;

interface DirectoryShareRepositoryInterface
{
    public function create(array $data): DirectoryShare;

    public function findById(string $id): ?DirectoryShare;

    public function delete(string $id): bool;

    public function getSharedWithMe(string $userId): Collection;

    public function findActiveShare(string $directoryId, string $userId): ?DirectoryShare;
}
