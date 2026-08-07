<?php

namespace App\Services;

use App\Models\StoragePackage;
use App\Models\UserSubscription;
use App\Repositories\Interfaces\StoragePackageRepositoryInterface;
use App\Repositories\Interfaces\UserSubscriptionRepositoryInterface;
use App\Services\Interfaces\StorageQuotaServiceInterface;
use App\Services\Interfaces\SubscriptionServiceInterface;

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
        \App\Models\Profile::where('user_id', $userId)->update([
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

    public function getSubscriptionHistory(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->subscriptionRepo->getAllForUser($userId);
    }
}
