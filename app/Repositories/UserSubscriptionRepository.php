<?php

namespace App\Repositories;

use App\Models\UserSubscription;
use App\Repositories\Interfaces\UserSubscriptionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserSubscriptionRepository implements UserSubscriptionRepositoryInterface
{
    public function create(array $data): UserSubscription
    {
        return UserSubscription::create($data);
    }

    public function findById(string $id): ?UserSubscription
    {
        return UserSubscription::find($id);
    }

    public function getActiveSubscription(string $userId): ?UserSubscription
    {
        return UserSubscription::active()
            ->where('user_id', $userId)
            ->with('storagePackage')
            ->first();
    }

    public function cancelActive(string $userId): void
    {
        UserSubscription::where('user_id', $userId)
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
    }

    public function getAllForUser(string $userId): Collection
    {
        return UserSubscription::where('user_id', $userId)
            ->with('storagePackage')
            ->latest()
            ->get();
    }
}
