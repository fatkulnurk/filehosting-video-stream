<?php

namespace App\Http\Controllers;

use App\Events\DirectoryCreated;
use App\Events\DirectoryDeleted;
use App\Http\Requests\DirectoryRequest;
use App\Http\Requests\MoveRequest;
use App\Models\Directory;
use App\Services\Interfaces\DirectoryServiceInterface;
use App\Services\Interfaces\FileServiceInterface;
use App\Services\Interfaces\StorageServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DirectoryController extends Controller
{
    public function __construct(
        private DirectoryServiceInterface $directoryService,
        private FileServiceInterface $fileService,
        private StorageServiceInterface $storageService
    ) {}

    public function index(Request $request)
    {
        return redirect()->route('files.index', $request->query());
    }

    public function show(Request $request, string $directory)
    {
        $directoryModel = $this->directoryService->getBreadcrumbs($directory)->first();

        if (! $directoryModel || $directoryModel->user_id !== auth()->id()) {
            abort(404);
        }

        $userId = auth()->id();
        $search = $request->input('search');

        $files = \App\Models\File::where('user_id', $userId)
            ->with('media')
            ->where('directory_id', $directory)
            ->when($search, fn ($q) => $q->where('name', 'LIKE', "%{$search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $directories = \App\Models\Directory::where('user_id', $userId)
            ->where('parent_id', $directory)
            ->withCount('files')
            ->orderBy('name')
            ->get();

        return Inertia::render('Files/Index', [
            'files' => $files,
            'directories' => $directories,
            'currentDirectory' => $directory,
            'breadcrumbs' => $this->directoryService->getBreadcrumbs($directory),
            'search' => $search,
            'directoryTree' => $this->directoryService->getTree($userId),
            'storageQuota' => [
                'used' => auth()->user()->storageUsed(),
                'limit' => auth()->user()->storageLimit(),
            ],
        ]);
    }

    public function store(DirectoryRequest $request)
    {
        $directory = $this->directoryService->create(
            $request->name,
            $request->parent_id,
            auth()->id()
        );

        event(new DirectoryCreated($directory));

        return redirect()->back()->with('success', "Folder '{$directory->name}' created");
    }

    public function update(DirectoryRequest $request, string $directory)
    {
        $directoryModel = $this->directoryService->getBreadcrumbs($directory)->first();

        if (! $directoryModel || $directoryModel->user_id !== auth()->id()) {
            abort(403);
        }

        $this->directoryService->update($directoryModel, $request->name);

        return redirect()->back()->with('success', 'Folder renamed');
    }

    public function move(MoveRequest $request, string $directory)
    {
        $directoryModel = $this->directoryService->getBreadcrumbs($directory)->first();

        if (! $directoryModel || $directoryModel->user_id !== auth()->id()) {
            abort(403);
        }

        $this->directoryService->move($directoryModel, $request->directory_id);

        return redirect()->back()->with('success', 'Folder moved');
    }

    public function destroy(string $directory)
    {
        $directoryModel = $this->directoryService->getBreadcrumbs($directory)->first();

        if (! $directoryModel || $directoryModel->user_id !== auth()->id()) {
            abort(403);
        }

        event(new DirectoryDeleted($directoryModel));

        $this->directoryService->delete($directoryModel);

        return redirect()->route('files.index')->with('success', 'Folder deleted');
    }
}
