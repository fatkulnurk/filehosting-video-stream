<?php

namespace App\Services\Interfaces;

use App\Models\File;

interface StatsServiceInterface
{
    public function recordView(File $file, ?string $referer = null): void;

    public function recordDownload(File $file): void;

    public function getDashboardStats(string $userId): array;

    public function getGlobalStats(): array;

    public function getFileStats(File $file): array;
}
