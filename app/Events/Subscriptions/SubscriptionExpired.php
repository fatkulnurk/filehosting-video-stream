<?php

declare(strict_types=1);

namespace App\Events\Subscriptions;

use App\Models\Users\User;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionExpired
{
    use Dispatchable;

    public function __construct(public User $user) {}
}
