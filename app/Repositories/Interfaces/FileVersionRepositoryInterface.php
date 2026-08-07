<?php

namespace App\Repositories\Interfaces;

use App\Models\FileVersion;
use Illuminate\Database\Eloquent\Collection;

interface FileVersionRepositoryInterface
{
    public function create(array $data): FileVersion;

    public function getForFile(string $fileId): Collection;

    public function getLatestVersion(string $fileId): ?FileVersion;

    public function findById(string $id): ?FileVersion;

    public function delete(string $id): bool;
}
