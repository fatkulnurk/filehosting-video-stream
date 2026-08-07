<?php

namespace App\Enums;

enum Role: string
{
    case Member = 'member';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Member',
            self::Admin => 'Administrator',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function permissions(): array
    {
        return match ($this) {
            self::Member => [
                'file.upload',
                'file.view',
                'file.download',
                'file.delete',
                'directory.create',
                'directory.view',
                'directory.delete',
                'storage.view',
                'storage.create',
                'storage.edit',
                'storage.delete',
                'profile.edit',
                'subscription.view',
                'api-key.manage',
                'webhook.manage',
            ],
            self::Admin => [
                'file.upload',
                'file.view',
                'file.download',
                'file.delete',
                'file.manage-all',
                'directory.create',
                'directory.view',
                'directory.delete',
                'directory.manage-all',
                'storage.create',
                'storage.view',
                'storage.edit',
                'storage.delete',
                'storage.manage-all',
                'user.view',
                'user.edit',
                'user.delete',
                'user.manage-all',
                'system.settings',
                'system.statistics',
                'subscription.manage',
                'subscription.view',
                'api-key.manage',
                'webhook.manage',
            ],
        };
    }

    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($role) => [$role->value => $role->label()])
            ->toArray();
    }
}
