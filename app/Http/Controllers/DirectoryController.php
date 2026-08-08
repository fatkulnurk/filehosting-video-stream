<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Directories\DirectoryServiceInterface;
use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Storage\StorageServiceInterface;
use App\Events\Directories\DirectoryCreated;
use App\Events\Directories\DirectoryDeleted;
use App\Http\Requests\Directories\DirectoryRequest;
use App\Http\Requests\Files\MoveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Concurrency;
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
        $this->directoryService->getOwnedDirectory(auth()->id(), $directory);

        $userId = auth()->id();
        $search = $request->input('search');

        [$files, $directories, $breadcrumbs, $directoryTree] = Concurrency::run([
            fn () => $this->fileService->paginateForDirectory($userId, $directory, [
                'search' => $search ?? '',
                'sort' => null,
                'direction' => 'asc',
                'per_page' => 20,
            ]),
            fn () => $this->directoryService->getChildDirectories($userId, $directory),
            fn () => $this->directoryService->getBreadcrumbs($directory),
            fn () => $this->directoryService->getTree($userId),
        ]);

        return Inertia::render('Files/Index', [
            'files' => $files,
            'directories' => $directories,
            'currentDirectory' => $directory,
            'breadcrumbs' => $breadcrumbs,
            'search' => $search,
            'directoryTree' => $directoryTree,
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
