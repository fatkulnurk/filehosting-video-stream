<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Subscriptions;

use App\Models\Subscriptions\UserSubscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserSubscriptionRepositoryInterface
{
    public function create(array $data): UserSubscription;

    public function findById(string $id): ?UserSubscription;

    public function getActiveSubscription(string $userId): ?UserSubscription;

    public function cancelActive(string $userId): void;

    public function getAllForUser(string $userId): Collection;

    public function update(string $id, array $data): UserSubscription;

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator;

    public function paginateForAdmin(array $params): LengthAwarePaginator;
}
