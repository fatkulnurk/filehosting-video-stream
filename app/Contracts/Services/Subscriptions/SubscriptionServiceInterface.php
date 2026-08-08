<?php

declare(strict_types=1);

namespace App\Contracts\Services\Subscriptions;

use App\Models\Subscriptions\StoragePackage;
use App\Models\Subscriptions\UserSubscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SubscriptionServiceInterface
{
    public function getUserPackage(string $userId): ?StoragePackage;

    public function userHasFeature(string $userId, string $feature): bool;

    public function getUserStorageLimit(string $userId): ?int;

    public function getUserMaxFileSize(string $userId): ?int;

    public function subscribe(string $userId, string $packageId, ?string $paymentMethod = null): UserSubscription;

    public function cancelActiveSubscription(string $userId): void;

    public function canUpload(string $userId, int $fileSize): bool;

    public function getSubscriptionHistory(string $userId): Collection;

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator;

    public function paginateAllForAdmin(array $params): LengthAwarePaginator;

    public function paginatePackages(array $params): LengthAwarePaginator;

    public function updateById(string $id, array $data): UserSubscription;

    public function getActivePackages(): Collection;

    public function getAllPackages(): Collection;

    public function getDefaultPackage(): ?StoragePackage;

    public function getPackageById(string $id): StoragePackage;

    public function createPackage(array $data): StoragePackage;

    public function updatePackage(string $id, array $data): StoragePackage;

    public function deletePackage(string $id): bool;

    public function packageHasSubscriptions(string $id): bool;
}
