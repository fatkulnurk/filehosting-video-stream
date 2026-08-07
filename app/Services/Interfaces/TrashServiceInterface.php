<?php

namespace App\Services\Interfaces;

use App\Models\File;
use Illuminate\Pagination\LengthAwarePaginator;

interface TrashServiceInterface
{
    public function softDeleteFile(File $file): bool;

    public function restoreFile(File $file): bool;

    public function forceDeleteFile(File $file): bool;

    public function getTrashedFiles(string $userId, int $perPage = 20): LengthAwarePaginator;

    public function purgeOlderThan(int $days): int;
}
