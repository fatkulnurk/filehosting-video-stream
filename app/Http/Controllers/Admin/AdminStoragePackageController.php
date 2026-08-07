<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PackageFeature;
use App\Http\Concerns\AppliesTableParams;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoragePackageRequest;
use App\Models\StoragePackage;
use App\Repositories\Interfaces\StoragePackageRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminStoragePackageController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private StoragePackageRepositoryInterface $packageRepo
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $packages = $this->applyTableQuery(
            $this->applyFilters(
                StoragePackage::query(),
                $request,
                [
                    'status' => [
                        'active' => fn ($q) => $q->where('is_active', true),
                        'inactive' => fn ($q) => $q->where('is_active', false),
                    ],
                ]
            ),
            $params,
            searchable: ['name'],
            sortable: [
                'name' => 'name',
                'storage' => 'storage_limit',
                'max_file' => 'max_file_size',
                'price' => 'price_monthly',
                'price_yearly' => 'price_yearly',
                'sort_order' => 'sort_order',
                'status' => 'is_active',
                'created_at' => 'created_at',
            ],
            defaultSort: 'sort_order',
            defaultDirection: 'asc',
        )->paginate($params['per_page'])->withQueryString();

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

        $this->packageRepo->create($data);

        return redirect()->route('admin.packages.index')->with('success', 'Package created');
    }

    public function update(StoragePackageRequest $request, string $id)
    {
        $data = $request->validated();
        $data['features'] = $request->features ?? [];
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);

        $this->packageRepo->update($id, $data);

        return redirect()->route('admin.packages.index')->with('success', 'Package updated');
    }

    public function destroy(string $id)
    {
        $package = StoragePackage::findOrFail($id);

        if ($package->subscriptions()->count() > 0) {
            return redirect()->route('admin.packages.index')
                ->with('error', 'Package has active subscriptions and cannot be deleted');
        }

        $this->packageRepo->delete($id);

        return redirect()->route('admin.packages.index')->with('success', 'Package deleted');
    }
}