<?php

declare(strict_types=1);

namespace App\Repositories\Subscriptions;

use App\Contracts\Repositories\Subscriptions\UserSubscriptionRepositoryInterface;
use App\Models\Subscriptions\UserSubscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function update(string $id, array $data): UserSubscription
    {
        $subscription = UserSubscription::findOrFail($id);
        $subscription->update($data);

        return $subscription->fresh();
    }

    public function paginateForUser(string $userId, array $params): LengthAwarePaginator
    {
        $sortable = [
            'status' => 'status',
            'start' => 'start_date',
            'end' => 'end_date',
            'package' => 'storage_package_id',
            'payment' => 'payment_method',
            'created_at' => 'created_at',
        ];

        $sorts = $params['sort'] ?? [];
        $status = $params['filters']['status'] ?? null;

        $query = UserSubscription::query()
            ->where('user_id', $userId)
            ->with('storagePackage');

        if ($status && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
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
            $query->orderByDesc('created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }

    public function paginateForAdmin(array $params): LengthAwarePaginator
    {
        $sortable = [
            'user' => 'users.name',
            'package' => 'storage_packages.name',
            'status' => 'user_subscriptions.status',
            'start' => 'user_subscriptions.start_date',
            'end' => 'user_subscriptions.end_date',
            'created_at' => 'user_subscriptions.created_at',
        ];

        $sorts = $params['sort'] ?? [];
        $status = $params['filters']['status'] ?? null;
        $packageId = $params['filters']['package_id'] ?? null;

        $query = UserSubscription::query()
            ->with(['user', 'storagePackage'])
            ->join('users', 'users.id', '=', 'user_subscriptions.user_id')
            ->join('storage_packages', 'storage_packages.id', '=', 'user_subscriptions.storage_package_id')
            ->select('user_subscriptions.*');

        if ($status && $status !== '' && $status !== 'all') {
            $query->where('user_subscriptions.status', $status);
        }

        if ($packageId && $packageId !== '') {
            $query->where('user_subscriptions.storage_package_id', $packageId);
        }

        if (($params['search'] ?? '') !== '') {
            $query->where(function ($q) use ($params) {
                $q->where('users.name', 'LIKE', '%'.$params['search'].'%')
                    ->orWhere('storage_packages.name', 'LIKE', '%'.$params['search'].'%');
            });
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
            $query->orderByDesc('user_subscriptions.created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }
}
