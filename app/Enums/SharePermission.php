<?php

declare(strict_types=1);

namespace App\Enums;

enum SharePermission: string
{
    case View = 'view';
    case Download = 'download';
    case Upload = 'upload';
    case Manage = 'manage';

    public function label(): string
    {
        return match ($this) {
            self::View => __('shares.permissions.view'),
            self::Download => __('shares.permissions.download'),
            self::Upload => __('shares.permissions.upload'),
            self::Manage => __('shares.permissions.manage'),
        };
    }

    public static function fileOnly(): array
    {
        return [self::View->value, self::Download->value];
    }

    public static function directoryOnly(): array
    {
        return [self::View->value, self::Upload->value, self::Manage->value];
    }
}
