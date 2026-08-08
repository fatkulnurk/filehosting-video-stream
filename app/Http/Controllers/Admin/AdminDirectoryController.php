<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\Admin\AdminServiceInterface;
use App\Contracts\Services\Directories\DirectoryServiceInterface;
use App\Contracts\Services\Files\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminDirectoryIndexRequest;
use App\Http\Requests\Admin\StoreDirectoryRequest;
use App\Http\Requests\Admin\UpdateDirectoryRequest;
use App\Traits\AppliesTableParams;
use Illuminate\Support\Facades\Concurrency;
use Inertia\Inertia;

class AdminDirectoryController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private DirectoryServiceInterface $directoryService,
        private FileServiceInterface $fileService,
        private AdminServiceInterface $adminService
    ) {}

    public function index(AdminDirectoryIndexRequest $request)
    {
        $params = $this->tableParams($request);
        $params['sort'] = $request->getSorts();
        $params['filters'] = $request->getFilters();

        [$directories, $users] = Concurrency::run([
            fn () => $this->directoryService->paginateAllForAdmin($params),
            fn () => $this->adminService->getUsersForSelect(),
        ]);

        return Inertia::render('Admin/Directories/Index', [
            'directories' => $directories,
            'users' => $users,
            'filters' => $this->tableFiltersWith($params, $request, ['user_id', 'status']),
        ]);
    }

    public function store(StoreDirectoryRequest $request)
    {
        $this->directoryService->create(
            $request->name,
            $request->parent_id,
            $request->user_id
        );

        return redirect()->back()->with('success', 'Directory created');
    }

    public function update(UpdateDirectoryRequest $request, string $id)
    {
        $directory = $this->directoryService->getDirectoryForAdmin($id);

        $this->directoryService->update($directory, $request->name);

        return redirect()->back()->with('success', 'Directory renamed');
    }

    public function destroy(string $id)
    {
        $directory = $this->directoryService->getDirectoryForAdmin($id);

        if (! $directory->trashed()) {
            $this->directoryService->delete($directory);
        }

        return redirect()->back()->with('success', 'Directory moved to trash');
    }

    public function forceDestroy(string $id)
    {
        $directory = $this->directoryService->getDirectoryForAdmin($id);

        $files = $this->fileService->getFilesInDirectoryWithTrashed($directory->id);

        foreach ($files as $file) {
            $this->fileService->forceDelete($file);
        }

        $this->directoryService->forceDelete($directory);

        return redirect()->route('admin.directories.index')->with('success', 'Directory permanently deleted');
    }
}
