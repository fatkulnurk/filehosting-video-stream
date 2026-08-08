<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\Subscriptions\SubscriptionServiceInterface;
use App\Enums\PackageFeature;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminStoragePackageIndexRequest;
use App\Http\Requests\Storage\StoragePackageRequest;
use App\Traits\AppliesTableParams;
use Inertia\Inertia;

class AdminStoragePackageController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private SubscriptionServiceInterface $subscriptionService
    ) {}

    public function index(AdminStoragePackageIndexRequest $request)
    {
        $params = $this->tableParams($request);
        $params['sort'] = $request->getSorts();
        $params['filters'] = $request->getFilters();

        $packages = $this->subscriptionService->paginatePackages($params);

        return Inertia::render('Admin/Packages/Index', [
            'packages' => $packages,
            'features' => PackageFeature::toSelectArray(),
            'filters' => $this->tableFiltersWith($params, $request, ['status']),
        ]);
    }

    public function store(StoragePackageRequest $request)
    {
        $data = $request->validated();
        $data['features'] = $request->features ?? [];
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);

        $this->subscriptionService->createPackage($data);

        return redirect()->route('admin.packages.index')->with('success', 'Package created');
    }

    public function update(StoragePackageRequest $request, string $id)
    {
        $data = $request->validated();
        $data['features'] = $request->features ?? [];
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);

        $this->subscriptionService->updatePackage($id, $data);

        return redirect()->route('admin.packages.index')->with('success', 'Package updated');
    }

    public function destroy(string $id)
    {
        $this->subscriptionService->getPackageById($id);

        if ($this->subscriptionService->packageHasSubscriptions($id)) {
            return redirect()->route('admin.packages.index')
                ->with('error', 'Package has active subscriptions and cannot be deleted');
        }

        $this->subscriptionService->deletePackage($id);

        return redirect()->route('admin.packages.index')->with('success', 'Package deleted');
    }
}
