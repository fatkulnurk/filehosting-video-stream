<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AppliesTableParams;
use App\Http\Requests\SubscribeRequest;
use App\Models\UserSubscription;
use App\Repositories\Interfaces\StoragePackageRepositoryInterface;
use App\Services\Interfaces\ActivityLogServiceInterface;
use App\Services\Interfaces\SubscriptionServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private SubscriptionServiceInterface $subscriptionService,
        private StoragePackageRepositoryInterface $packageRepo,
        private ActivityLogServiceInterface $activityLog
    ) {}

    public function index(Request $request)
    {
        $userId = auth()->id();
        $currentPackage = $this->subscriptionService->getUserPackage($userId);

        $params = $this->tableParams($request);

        $history = $this->applyFilters(
            $this->applyTableQuery(
                UserSubscription::query()
                    ->where('user_id', $userId)
                    ->with('storagePackage'),
                $params,
                searchable: [],
                sortable: [
                    'status' => 'status',
                    'start' => 'start_date',
                    'end' => 'end_date',
                    'package' => 'storage_package_id',
                    'payment' => 'payment_method',
                    'created_at' => 'created_at',
                ],
                defaultSort: 'created_at',
                defaultDirection: 'desc',
            ),
            $request,
            ['status' => 'status'],
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Subscriptions/Index', [
            'packages' => $this->packageRepo->getActive(),
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