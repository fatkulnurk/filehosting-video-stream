<?php

declare(strict_types=1);

namespace App\Services\Subscriptions;

use App\Contracts\Repositories\Storage\StoragePackageRepositoryInterface;
use App\Contracts\Repositories\Subscriptions\UserSubscriptionRepositoryInterface;
use App\Contracts\Services\Storage\StorageQuotaServiceInterface;
use App\Contracts\Services\Subscriptions\SubscriptionServiceInterface;
use App\Models\Subscriptions\StoragePackage;
use App\Models\Subscriptions\UserSubscription;
use App\Models\Users\Profile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        private StoragePackageRepositoryInterface $packageRepo,
        private UserSubscriptionRepositoryInterface $subscriptionRepo,
        private StorageQuotaServiceInterface $quotaService
    ) {}

    public function getUserPackage(string $userId): ?StoragePackage
    {
        return $this->subscriptionRepo->getActiveSubscription($userId)?->storagePackage;
    }

    public function userHasFeature(string $userId, string $feature): bool
    {
        $package = $this->getUserPackage($userId);

        if (! $package) {
            return false;
        }

        return $package->hasFeature($feature);
    }

    public function getUserStorageLimit(string $userId): ?int
    {
        return $this->getUserPackage($userId)?->storage_limit;
    }

    public function getUserMaxFileSize(string $userId): ?int
    {
        return $this->getUserPackage($userId)?->max_file_size;
    }

    public function subscribe(string $userId, string $packageId, ?string $paymentMethod = null): UserSubscription
    {
        $package = $this->packageRepo->findById($packageId);

        if (! $package || ! $package->is_active) {
            throw new \InvalidArgumentException('Package not found or inactive');
        }

        $this->cancelActiveSubscription($userId);

        $subscription = $this->subscriptionRepo->create([
            'user_id' => $userId,
            'storage_package_id' => $packageId,
            'status' => 'active',
            'start_date' => now(),
            'end_date' => $package->price_monthly > 0 ? now()->addMonth() : null,
            'payment_method' => $paymentMethod,
        ]);

        // Sync quota from package
        Profile::where('user_id', $userId)->update([
            'storage_limit' => $package->storage_limit,
        ]);

        return $subscription;
    }

    public function cancelActiveSubscription(string $userId): void
    {
        $this->subscriptionRepo->cancelActive($userId);
    }

    public function canUpload(string $userId, int $fileSize): bool
    {
        $maxFileSize = $this->getUserMaxFileSize($userId);

        if ($maxFileSize && $fileSize > $maxFileSize) {
            return false;
        }

        return $this->quotaService->canUpload($userId, $fileSize);
    }

    public function getSubscriptionHistory(string $userId): Collection
    {
        return $this->subscriptionRepo->getAllForUser($userId);
    }

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator
    {
        return $this->subscriptionRepo->paginateForUser($userId, $params);
    }

    public function paginateAllForAdmin(array $params): LengthAwarePaginator
    {
        return $this->subscriptionRepo->paginateForAdmin($params);
    }

    public function paginatePackages(array $params): LengthAwarePaginator
    {
        return $this->packageRepo->paginate($params);
    }

    public function updateById(string $id, array $data): UserSubscription
    {
        return $this->subscriptionRepo->update($id, $data);
    }

    public function getActivePackages(): Collection
    {
        return $this->packageRepo->getActive();
    }

    public function getAllPackages(): Collection
    {
        return $this->packageRepo->getAllOrderedByName();
    }

    public function getDefaultPackage(): ?StoragePackage
    {
        return $this->packageRepo->getDefault();
    }

    public function getPackageById(string $id): StoragePackage
    {
        $package = $this->packageRepo->findById($id);

        if (! $package) {
            abort(404);
        }

        return $package;
    }

    public function createPackage(array $data): StoragePackage
    {
        return $this->packageRepo->create($data);
    }

    public function updatePackage(string $id, array $data): StoragePackage
    {
        return $this->packageRepo->update($id, $data);
    }

    public function deletePackage(string $id): bool
    {
        return $this->packageRepo->delete($id);
    }

    public function packageHasSubscriptions(string $id): bool
    {
        return $this->packageRepo->countSubscriptions($id) > 0;
    }
}
