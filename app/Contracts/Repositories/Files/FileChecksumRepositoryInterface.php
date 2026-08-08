<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Files;

use App\Models\Files\FileChecksum;
use Illuminate\Database\Eloquent\Collection;

interface FileChecksumRepositoryInterface
{
    public function create(array $data): FileChecksum;

    public function findById(string $id): ?FileChecksum;

    public function findByCrc32(int $crc32, int $size): Collection;

    public function incrementReference(string $id): void;

    public function decrementReference(string $id): void;
}
