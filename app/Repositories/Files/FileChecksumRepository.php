<?php

declare(strict_types=1);

namespace App\Repositories\Files;

use App\Contracts\Repositories\Files\FileChecksumRepositoryInterface;
use App\Models\Files\FileChecksum;
use Illuminate\Database\Eloquent\Collection;

class FileChecksumRepository implements FileChecksumRepositoryInterface
{
    public function create(array $data): FileChecksum
    {
        return FileChecksum::create($data);
    }

    public function findById(string $id): ?FileChecksum
    {
        return FileChecksum::find($id);
    }

    public function findByCrc32(int $crc32, int $size): Collection
    {
        return FileChecksum::where('crc32', $crc32)
            ->where('size', $size)
            ->get();
    }

    public function incrementReference(string $id): void
    {
        FileChecksum::where('id', $id)->increment('reference_count');
    }

    public function decrementReference(string $id): void
    {
        FileChecksum::where('id', $id)->decrement('reference_count');
    }
}
