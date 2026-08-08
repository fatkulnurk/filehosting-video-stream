<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Files;

use App\Models\Files\FileVersion;
use Illuminate\Database\Eloquent\Collection;

interface FileVersionRepositoryInterface
{
    public function create(array $data): FileVersion;

    public function getForFile(string $fileId): Collection;

    public function getLatestVersion(string $fileId): ?FileVersion;

    public function findById(string $id): ?FileVersion;

    public function delete(string $id): bool;
}
