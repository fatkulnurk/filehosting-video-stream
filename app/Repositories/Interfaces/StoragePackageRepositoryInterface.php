<?php

namespace App\Repositories\Interfaces;

use App\Models\StoragePackage;
use Illuminate\Database\Eloquent\Collection;

interface StoragePackageRepositoryInterface
{
    public function create(array $data): StoragePackage;

    public function findById(string $id): ?StoragePackage;

    public function getAll(): Collection;

    public function getActive(): Collection;

    public function getDefault(): ?StoragePackage;

    public function update(string $id, array $data): StoragePackage;

    public function delete(string $id): bool;
}
