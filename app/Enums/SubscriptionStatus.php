<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('subscriptions.status.active'),
            self::Cancelled => __('subscriptions.status.cancelled'),
            self::Expired => __('subscriptions.status.expired'),
        };
    }
}
