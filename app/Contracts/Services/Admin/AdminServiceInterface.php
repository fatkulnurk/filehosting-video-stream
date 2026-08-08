<?php

declare(strict_types=1);

namespace App\Contracts\Services\Admin;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdminServiceInterface
{
    public function paginateUsers(int $perPage = 20, ?string $search = null): LengthAwarePaginator;

    public function updateUser(User $user, array $data): User;

    public function deleteUser(User $user): bool;

    public function changeRole(User $user, string $role): User;

    public function setQuota(User $user, ?int $storageLimit): void;

    public function getGlobalStats(): array;

    public function paginateUsersWithParams(array $params): LengthAwarePaginator;

    public function getUserById(string $id): User;

    public function findUserById(string $id): User;

    public function getUsersForSelect(): Collection;

    public function createUser(array $data): User;

    public function getLatestUsers(int $limit = 10): Collection;

    public function getLatestStorageServers(int $limit = 10): Collection;
}
