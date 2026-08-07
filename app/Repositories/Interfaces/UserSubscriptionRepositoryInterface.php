<?php

namespace App\Repositories\Interfaces;

use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Collection;

interface UserSubscriptionRepositoryInterface
{
    public function create(array $data): UserSubscription;

    public function findById(string $id): ?UserSubscription;

    public function getActiveSubscription(string $userId): ?UserSubscription;

    public function cancelActive(string $userId): void;

    public function getAllForUser(string $userId): Collection;
}
