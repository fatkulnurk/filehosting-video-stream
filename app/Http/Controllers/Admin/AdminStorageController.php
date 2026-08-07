<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StorageDriver;
use App\Http\Controllers\Controller;
use App\Http\Concerns\AppliesTableParams;
use App\Http\Requests\StorageServerRequest;
use App\Models\StorageServer;
use App\Services\Interfaces\StorageServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminStorageController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private StorageServiceInterface $storageService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $servers = $this->applyTableQuery(
            $this->applyFilters(
                StorageServer::query(),
                $request,
                [
                    'driver' => 'driver',
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
                'driver' => 'driver',
                'storage_used' => 'storage_used',
                'created_at' => 'created_at',
            ],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        $backupOptions = StorageServer::where('is_active', true)
            ->where('is_primary', false)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Storage/Index', [
            'servers' => $servers,
            'drivers' => StorageDriver::toSelectArray(),
            'driverFields' => collect(StorageDriver::cases())->mapWithKeys(fn ($d) => [$d->value => $d->requiredFields()]),
            'backupOptions' => $backupOptions,
            'filters' => $this->tableFiltersWith($params, $request, ['driver', 'status']),
        ]);
    }

    public function show(string $id)
    {
        $server = StorageServer::with('backupStorageServer')->findOrFail($id);

        return Inertia::render('Admin/Storage/Show', [
            'server' => $server,
        ]);
    }

    public function store(StorageServerRequest $request)
    {
        $server = $this->storageService->addStorage([
            'name' => $request->name,
            'driver' => $request->driver,
            'config' => $request->config ?? [],
            'is_primary' => $request->boolean('is_primary'),
            'backup_storage_server_id' => $request->backup_storage_server_id,
            'auto_backup' => $request->boolean('auto_backup'),
            'storage_limit' => $request->storage_limit,
        ]);

        return redirect()->route('admin.storage.index')->with('success', "Storage '{$server->name}' added");
    }

    public function update(StorageServerRequest $request, string $id)
    {
        $server = $this->storageService->updateStorage($id, [
            'name' => $request->name,
            'driver' => $request->driver,
            'config' => $request->config ?? [],
            'is_primary' => $request->boolean('is_primary'),
            'backup_storage_server_id' => $request->backup_storage_server_id,
            'auto_backup' => $request->boolean('auto_backup'),
            'storage_limit' => $request->storage_limit,
        ]);

        return redirect()->route('admin.storage.index')->with('success', "Storage '{$server->name}' updated");
    }

    public function deactivate(Request $request, string $id)
    {
        $server = StorageServer::findOrFail($id);

        $this->authorize('delete', $server);

        $this->storageService->deactivateStorage($server, $request->target_storage_id);

        return redirect()->route('admin.storage.index')->with('success', 'Storage server deactivated');
    }

    public function destroy(string $id)
    {
        $server = StorageServer::findOrFail($id);

        $this->authorize('delete', $server);

        $this->storageService->deleteStorage($id);

        return redirect()->route('admin.storage.index')->with('success', 'Storage server deleted');
    }
}