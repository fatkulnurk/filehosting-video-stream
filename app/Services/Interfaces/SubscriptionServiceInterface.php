<?php

namespace App\Services\Interfaces;

use App\Models\StoragePackage;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Collection;

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
}
