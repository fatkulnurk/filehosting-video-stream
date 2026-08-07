<?php

namespace App\Repositories;

use App\Models\StoragePackage;
use App\Repositories\Interfaces\StoragePackageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StoragePackageRepository implements StoragePackageRepositoryInterface
{
    public function create(array $data): StoragePackage
    {
        return StoragePackage::create($data);
    }

    public function findById(string $id): ?StoragePackage
    {
        return StoragePackage::find($id);
    }

    public function getAll(): Collection
    {
        return StoragePackage::orderBy('sort_order')->get();
    }

    public function getActive(): Collection
    {
        return StoragePackage::active()->get();
    }

    public function getDefault(): ?StoragePackage
    {
        return StoragePackage::default()->active()->first();
    }

    public function update(string $id, array $data): StoragePackage
    {
        $package = StoragePackage::findOrFail($id);
        $package->update($data);

        return $package->fresh();
    }

    public function delete(string $id): bool
    {
        return StoragePackage::where('id', $id)->delete() > 0;
    }
}
