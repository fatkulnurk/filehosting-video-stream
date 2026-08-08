<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\ActivityLogs\ActivityLogServiceInterface;
use App\Contracts\Services\Subscriptions\SubscriptionServiceInterface;
use App\Http\Requests\Subscriptions\SubscribeRequest;
use App\Http\Requests\Subscriptions\SubscriptionIndexRequest;
use App\Traits\AppliesTableParams;
use Illuminate\Support\Facades\Concurrency;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private SubscriptionServiceInterface $subscriptionService,
        private ActivityLogServiceInterface $activityLog
    ) {}

    public function index(SubscriptionIndexRequest $request)
    {
        $userId = auth()->id();

        $params = $this->tableParams($request);
        $params['sort'] = $request->getSorts();
        $params['filters'] = $request->getFilters();

        [$currentPackage, $history, $packages] = Concurrency::run([
            fn () => $this->subscriptionService->getUserPackage($userId),
            fn () => $this->subscriptionService->paginateForUser($userId, $params),
            fn () => $this->subscriptionService->getActivePackages(),
        ]);

        return Inertia::render('Subscriptions/Index', [
            'packages' => $packages,
            'currentPackage' => $currentPackage,
            'history' => $history,
            'filters' => $this->tableFiltersWith($params, $request, ['status']),
        ]);
    }

    public function subscribe(SubscribeRequest $request)
    {
        $subscription = $this->subscriptionService->subscribe(
            auth()->id(),
            $request->package_id,
            $request->payment_method
        );

        $this->activityLog->log('subscription.change', auth()->id(), properties: [
            'package' => $subscription->storagePackage->name,
        ]);

        return redirect()->route('subscriptions.index')->with('success', 'Subscription updated');
    }

    public function cancel()
    {
        $this->subscriptionService->cancelActiveSubscription(auth()->id());

        return redirect()->route('subscriptions.index')->with('success', 'Subscription cancelled');
    }
}
