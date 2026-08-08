<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\Storage\StorageServiceInterface;
use App\Enums\StorageDriver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminStorageIndexRequest;
use App\Http\Requests\Storage\StorageServerRequest;
use App\Traits\AppliesTableParams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Concurrency;
use Inertia\Inertia;

class AdminStorageController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private StorageServiceInterface $storageService
    ) {}

    public function index(AdminStorageIndexRequest $request)
    {
        $params = $this->tableParams($request);
        $params['sort'] = $request->getSorts();
        $params['filters'] = $request->getFilters();

        [$servers, $backupOptions] = Concurrency::run([
            fn () => $this->storageService->paginateServers($params),
            fn () => $this->storageService->getBackupOptions(),
        ]);

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
        $server = $this->storageService->getServerById($id)->load('backupStorageServer');

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
        $server = $this->storageService->getServerById($id);

        $this->authorize('delete', $server);

        $this->storageService->deactivateStorage($server, $request->target_storage_id);

        return redirect()->route('admin.storage.index')->with('success', 'Storage server deactivated');
    }

    public function destroy(string $id)
    {
        $server = $this->storageService->getServerById($id);

        $this->authorize('delete', $server);

        $this->storageService->deleteStorage($id);

        return redirect()->route('admin.storage.index')->with('success', 'Storage server deleted');
    }
}
