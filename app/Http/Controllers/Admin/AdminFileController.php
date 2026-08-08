<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\Admin\AdminServiceInterface;
use App\Contracts\Services\Directories\DirectoryServiceInterface;
use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Files\StatsServiceInterface;
use App\Contracts\Services\Files\SubtitleServiceInterface;
use App\Contracts\Services\Files\VersioningServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminFileIndexRequest;
use App\Http\Requests\Admin\StoreFileRequest;
use App\Http\Requests\Admin\UpdateFileRequest;
use App\Http\Requests\Admin\UserDirectoriesRequest;
use App\Traits\AppliesTableParams;
use Illuminate\Support\Facades\Concurrency;
use Inertia\Inertia;

class AdminFileController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private FileServiceInterface $fileService,
        private StatsServiceInterface $statsService,
        private SubtitleServiceInterface $subtitleService,
        private VersioningServiceInterface $versioningService,
        private AdminServiceInterface $adminService,
        private DirectoryServiceInterface $directoryService
    ) {}

    public function index(AdminFileIndexRequest $request)
    {
        $params = $this->tableParams($request);
        $params['sort'] = $request->getSorts();
        $params['filters'] = $request->getFilters();

        [$files, $users] = Concurrency::run([
            fn () => $this->fileService->paginateAllForAdmin($params),
            fn () => $this->adminService->getUsersForSelect(),
        ]);

        return Inertia::render('Admin/Files/Index', [
            'files' => $files,
            'users' => $users,
            'filters' => $this->tableFiltersWith($params, $request, ['user_id', 'status', 'type']),
        ]);
    }

    public function show(string $id)
    {
        $file = $this->fileService->getFileForAdmin($id);

        [$subtitles, $stats, $versions] = Concurrency::run([
            fn () => $this->subtitleService->getTracks($file),
            fn () => $this->statsService->getFileStats($file),
            fn () => $this->versioningService->getVersions($file),
        ]);

        return Inertia::render('Admin/Files/Show', [
            'file' => $file,
            'streamUrl' => $file->signed_stream_url,
            'subtitles' => $subtitles,
            'stats' => $stats,
            'versions' => $versions,
        ]);
    }

    public function update(UpdateFileRequest $request, string $id)
    {
        $file = $this->fileService->getFileForAdmin($id);

        $data = $request->validated();

        $this->fileService->rename($file, $data['name']);

        if (array_key_exists('is_public', $data)) {
            $this->fileService->updateSettings($file, ['is_public' => (bool) $data['is_public']]);
        }

        return redirect()->back()->with('success', 'File updated');
    }

    public function destroy(string $id)
    {
        $file = $this->fileService->getFileForAdmin($id);

        if (! $file->trashed()) {
            $this->fileService->delete($file);
        }

        return redirect()->back()->with('success', 'File moved to trash');
    }

    public function forceDestroy(string $id)
    {
        $file = $this->fileService->getFileForAdmin($id);

        if ($file->trashed()) {
            $this->fileService->forceDelete($file);
        } else {
            $this->fileService->delete($file);
            $this->fileService->forceDelete($file->fresh());
        }

        return redirect()->route('admin.files.index')->with('success', 'File permanently deleted');
    }

    public function store(StoreFileRequest $request)
    {
        $userId = $request->user_id;

        $successCount = 0;

        foreach ($request->file('files', []) as $file) {
            try {
                $this->fileService->store($file, $request->directory_id, $userId);
                $successCount++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->back()->with(
            'success',
            $successCount > 0
                ? "{$successCount} file(s) uploaded for {$request->user_id}"
                : 'No files uploaded'
        );
    }

    public function directories(UserDirectoriesRequest $request)
    {
        $tree = $this->directoryService->getTree($request->query('user_id'));

        $rows = [];
        $walk = function ($dirs, array $ancestors = []) use (&$walk, &$rows) {
            foreach ($dirs as $dir) {
                $rows[] = [
                    'id' => $dir->id,
                    'label' => [...$ancestors, $dir->name],
                ];
                $walk($dir->children ?? collect(), [...$ancestors, $dir->name]);
            }
        };
        $walk($tree);

        return response()->json(['directories' => $rows]);
    }
}
