<?php

declare(strict_types=1);

namespace App\Contracts\Services\Files;

use App\Models\Files\File;
use Illuminate\Pagination\LengthAwarePaginator;

interface TrashServiceInterface
{
    public function softDeleteFile(File $file): bool;

    public function restoreFile(File $file): bool;

    public function forceDeleteFile(File $file): bool;

    public function getTrashedFiles(string $userId, int $perPage = 20): LengthAwarePaginator;

    public function purgeOlderThan(int $days): int;

    public function purgeTrashed(string $userId): int;
}
