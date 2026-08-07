<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Concerns\AppliesTableParams;
use App\Models\StoragePackage;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Interfaces\SubscriptionServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSubscriptionController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private SubscriptionServiceInterface $subscriptionService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $query = UserSubscription::query()
            ->with(['user', 'storagePackage'])
            ->join('users', 'users.id', '=', 'user_subscriptions.user_id')
            ->join('storage_packages', 'storage_packages.id', '=', 'user_subscriptions.storage_package_id')
            ->select('user_subscriptions.*');

        $query = $this->applyFilters(
            $query,
            $request,
            [
                'status' => 'user_subscriptions.status',
                'package_id' => 'user_subscriptions.storage_package_id',
            ]
        );

        $subscriptions = $this->applyTableQuery(
            $query,
            $params,
            searchable: ['users.name', 'storage_packages.name'],
            sortable: [
                'user' => 'users.name',
                'package' => 'storage_packages.name',
                'status' => 'user_subscriptions.status',
                'start' => 'user_subscriptions.start_date',
                'end' => 'user_subscriptions.end_date',
                'created_at' => 'user_subscriptions.created_at',
            ],
            defaultSort: 'user_subscriptions.created_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'packages' => StoragePackage::orderBy('name')->get(),
            'filters' => $this->tableFiltersWith($params, $request, ['status', 'package_id']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'package_id' => ['required', 'uuid', 'exists:storage_packages,id'],
            'status' => ['sometimes', 'string', 'in:active,cancelled,expired'],
        ]);

        try {
            $subscription = $this->subscriptionService->subscribe(
                $request->user_id,
                $request->package_id
            );

            if ($request->filled('status') && $request->status !== 'active') {
                $subscription->update(['status' => $request->status]);
            }

            return redirect()->route('admin.subscriptions.index')->with('success', 'Subscription assigned');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, string $id)
    {
        $subscription = UserSubscription::findOrFail($id);

        $data = $request->validate([
            'package_id' => ['sometimes', 'uuid', 'exists:storage_packages,id'],
            'status' => ['sometimes', 'string', 'in:active,cancelled,expired'],
            'end_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $subscription->update($data);

        return redirect()->back()->with('success', 'Subscription updated');
    }

    public function cancel(string $userId)
    {
        $user = User::findOrFail($userId);

        $this->subscriptionService->cancelActiveSubscription($user->id);

        return redirect()->back()->with('success', 'Active subscription cancelled');
    }
}
