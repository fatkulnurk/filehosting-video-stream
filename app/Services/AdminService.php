<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\ActivityLogRepositoryInterface;
use App\Services\Interfaces\AdminServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminService implements AdminServiceInterface
{
    public function __construct(
        private ActivityLogRepositoryInterface $activityLogRepo
    ) {}

    public function paginateUsers(int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        return User::query()
            ->with('profile')
            ->when($search, fn ($q) => $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function updateUser(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function deleteUser(User $user): bool
    {
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            throw new \InvalidArgumentException('Cannot delete the last admin');
        }

        return $user->delete() !== false;
    }

    public function changeRole(User $user, string $role): User
    {
        if (! in_array($role, ['member', 'admin'])) {
            throw new \InvalidArgumentException('Invalid role');
        }

        if ($user->isAdmin() && $role === 'member' && User::where('role', 'admin')->count() <= 1) {
            throw new \InvalidArgumentException('Cannot demote the last admin');
        }

        $user->update(['role' => $role]);

        return $user->fresh();
    }

    public function setQuota(User $user, ?int $storageLimit): void
    {
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['storage_limit' => $storageLimit]
        );
    }

    public function getGlobalStats(): array
    {
        return app(\App\Services\Interfaces\StatsServiceInterface::class)->getGlobalStats();
    }
}
