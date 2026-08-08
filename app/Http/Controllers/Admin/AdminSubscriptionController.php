<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\Admin\AdminServiceInterface;
use App\Contracts\Services\Subscriptions\SubscriptionServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionRequest;
use App\Http\Requests\Admin\UpdateSubscriptionRequest;
use App\Traits\AppliesTableParams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Concurrency;
use Inertia\Inertia;

class AdminSubscriptionController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private SubscriptionServiceInterface $subscriptionService,
        private AdminServiceInterface $adminService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);
        $params['filters'] = $request->only(['status', 'package_id']);

        [$subscriptions, $users, $packages] = Concurrency::run([
            fn () => $this->subscriptionService->paginateAllForAdmin($params),
            fn () => $this->adminService->getUsersForSelect(),
            fn () => $this->subscriptionService->getAllPackages(),
        ]);

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'users' => $users,
            'packages' => $packages,
            'filters' => $this->tableFiltersWith($params, $request, ['status', 'package_id']),
        ]);
    }

    public function store(StoreSubscriptionRequest $request)
    {
        try {
            $subscription = $this->subscriptionService->subscribe(
                $request->user_id,
                $request->package_id
            );

            if ($request->filled('status') && $request->status !== 'active') {
                $this->subscriptionService->updateById($subscription->id, ['status' => $request->status]);
            }

            return redirect()->route('admin.subscriptions.index')->with('success', 'Subscription assigned');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function update(UpdateSubscriptionRequest $request, string $id)
    {
        $this->subscriptionService->updateById($id, $request->validated());

        return redirect()->back()->with('success', 'Subscription updated');
    }

    public function cancel(string $userId)
    {
        $this->subscriptionService->cancelActiveSubscription($userId);

        return redirect()->back()->with('success', 'Active subscription cancelled');
    }
}
