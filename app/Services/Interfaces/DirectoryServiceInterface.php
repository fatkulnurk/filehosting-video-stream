<?php

namespace App\Services\Interfaces;

use App\Models\Directory;
use Illuminate\Support\Collection;

interface DirectoryServiceInterface
{
    public function create(string $name, ?string $parentId, string $userId): Directory;

    public function delete(Directory $directory): bool;

    public function update(Directory $directory, string $name): Directory;

    public function move(Directory $directory, ?string $newParentId): Directory;

    public function getTree(string $userId): Collection;

    public function getPath(string $id, string $userId): Collection;

    public function getBreadcrumbs(string $id): Collection;

    public function getSubdirectoryIds(Directory $directory): array;
}
