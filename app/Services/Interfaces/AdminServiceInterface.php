<?php

namespace App\Services\Interfaces;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdminServiceInterface
{
    public function paginateUsers(int $perPage = 20, ?string $search = null): LengthAwarePaginator;

    public function updateUser(User $user, array $data): User;

    public function deleteUser(User $user): bool;

    public function changeRole(User $user, string $role): User;

    public function setQuota(User $user, ?int $storageLimit): void;

    public function getGlobalStats(): array;
}
