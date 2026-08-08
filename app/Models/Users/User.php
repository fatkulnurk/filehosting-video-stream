<?php

declare(strict_types=1);

namespace App\Models\Users;

use App\Enums\Role;
use App\Models\Files\Directory;
use App\Models\Files\File;
use App\Models\Files\FileFavorite;
use App\Models\Integrations\ApiKey;
use App\Models\Integrations\Webhook;
use App\Models\Storage\StorageServer;
use App\Models\Subscriptions\UserSubscription;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function directories(): HasMany
    {
        return $this->hasMany(Directory::class);
    }

    public function storageServers(): HasMany
    {
        return $this->hasMany(StorageServer::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(FileFavorite::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isMember(): bool
    {
        return $this->role === Role::Member;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->role->permissions());
    }

    public function storageLimit(): ?int
    {
        return $this->profile?->storage_limit;
    }

    public function storageUsed(): int
    {
        return $this->profile?->storage_used ?? 0;
    }
}
