<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Contracts\Repositories\ActivityLogs\ActivityLogRepositoryInterface;
use App\Contracts\Services\Admin\AdminServiceInterface;
use App\Contracts\Services\Files\StatsServiceInterface;
use App\Models\Storage\StorageServer;
use App\Models\Users\User;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

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
        return app(StatsServiceInterface::class)->getGlobalStats();
    }

    public function paginateUsersWithParams(array $params): LengthAwarePaginator
    {
        $sortable = [
            'name' => 'name',
            'email' => 'email',
            'role' => 'role',
            'files' => 'files_count',
            'storage_used' => \DB::raw('(SELECT COALESCE(storage_used,0) FROM profiles WHERE profiles.user_id = users.id)'),
            'storage_limit' => \DB::raw('(SELECT COALESCE(storage_limit,0) FROM profiles WHERE profiles.user_id = users.id)'),
            'created_at' => 'created_at',
        ];

        $sort = $params['sort'] ?? null;
        $direction = in_array($params['direction'] ?? 'asc', ['asc', 'desc'], true) ? $params['direction'] : 'asc';
        $role = $params['filters']['role'] ?? null;

        $query = User::query()->with('profile')->withCount('files');

        if ($role && $role !== '' && $role !== 'all') {
            $query->where('role', $role);
        }

        if (($params['search'] ?? '') !== '') {
            $query->where(function ($q) use ($params) {
                $q->where('name', 'LIKE', '%'.$params['search'].'%')
                    ->orWhere('email', 'LIKE', '%'.$params['search'].'%');
            });
        }

        if ($sort && isset($sortable[$sort])) {
            $target = $sortable[$sort];
            if ($target instanceof Expression) {
                $query->orderByRaw($target->getValue($query->getQuery()->getGrammar()).' '.$direction);
            } else {
                $query->orderBy($target, $direction);
            }
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }

    public function getUserById(string $id): User
    {
        return User::with(['profile', 'subscriptions.storagePackage'])->findOrFail($id);
    }

    public function findUserById(string $id): User
    {
        return User::findOrFail($id);
    }

    public function getUsersForSelect(): Collection
    {
        return User::orderBy('name')->get(['id', 'name', 'email']);
    }

    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'member',
            'email_verified_at' => now(),
        ]);

        $user->profile()->create([
            'storage_limit' => $data['storage_limit'] ?? null,
            'storage_used' => 0,
        ]);

        return $user;
    }

    public function getLatestUsers(int $limit = 10): Collection
    {
        return User::latest()->limit($limit)->get();
    }

    public function getLatestStorageServers(int $limit = 10): Collection
    {
        return StorageServer::latest()->limit($limit)->get();
    }
}
