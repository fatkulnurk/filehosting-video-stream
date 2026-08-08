<?php

declare(strict_types=1);

namespace App\Jobs\Subscriptions;

use App\Events\Subscriptions\SubscriptionExpired;
use App\Models\Subscriptions\UserSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        UserSubscription::where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now())
            ->get()
            ->each(function (UserSubscription $subscription) {
                $subscription->update(['status' => 'expired']);

                event(new SubscriptionExpired($subscription->user));
            });
    }
}
