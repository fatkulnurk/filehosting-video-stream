<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Storage;

use App\Models\Subscriptions\StoragePackage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface StoragePackageRepositoryInterface
{
    public function create(array $data): StoragePackage;

    public function findById(string $id): ?StoragePackage;

    public function getAll(): Collection;

    public function getActive(): Collection;

    public function getDefault(): ?StoragePackage;

    public function getAllOrderedByName(): Collection;

    public function countSubscriptions(string $id): int;

    public function paginate(array $params): LengthAwarePaginator;

    public function update(string $id, array $data): StoragePackage;

    public function delete(string $id): bool;
}
