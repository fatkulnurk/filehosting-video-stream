<?php

declare(strict_types=1);

namespace App\Repositories\Storage;

use App\Contracts\Repositories\Storage\StoragePackageRepositoryInterface;
use App\Enums\StorageStatusFilter;
use App\Models\Subscriptions\StoragePackage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function getAllOrderedByName(): Collection
    {
        return StoragePackage::orderBy('name')->get();
    }

    public function countSubscriptions(string $id): int
    {
        return StoragePackage::findOrFail($id)->subscriptions()->count();
    }

    public function paginate(array $params): LengthAwarePaginator
    {
        $sortable = [
            'name' => 'name',
            'storage' => 'storage_limit',
            'max_file' => 'max_file_size',
            'price' => 'price_monthly',
            'price_yearly' => 'price_yearly',
            'sort_order' => 'sort_order',
            'status' => 'is_active',
            'created_at' => 'created_at',
        ];

        $sorts = $params['sort'] ?? [];
        $status = $params['filters']['status'] ?? null;

        $query = StoragePackage::query();

        if ($status && $status !== 'all') {
            match (StorageStatusFilter::tryFrom($status)) {
                StorageStatusFilter::Active => $query->where('is_active', true),
                StorageStatusFilter::Inactive => $query->where('is_active', false),
                default => null,
            };
        }

        if (($params['search'] ?? '') !== '') {
            $query->where('name', 'LIKE', '%'.$params['search'].'%');
        }

        if ($sorts) {
            foreach ($sorts as $field => $direction) {
                if (! isset($sortable[$field])) {
                    continue;
                }

                $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
                $query->orderBy($sortable[$field], $direction);
            }
        } else {
            $query->orderBy('sort_order');
        }

        return $query->paginate($params['per_page'])->withQueryString();
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
