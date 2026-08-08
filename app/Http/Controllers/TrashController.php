<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Files\TrashServiceInterface;
use App\Traits\AppliesTableParams;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrashController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private TrashServiceInterface $trashService,
        private FileServiceInterface $fileService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $files = $this->fileService->paginateTrashedForUser(auth()->id(), $params);

        return Inertia::render('Files/Trash', [
            'files' => $files,
            'empty' => $files->isEmpty(),
            'filters' => $this->tableFilters($params),
        ]);
    }

    public function restore(string $id)
    {
        $file = $this->fileService->getTrashedFile(auth()->id(), $id);

        $this->trashService->restoreFile($file);

        return redirect()->route('trash.index')->with('success', 'File restored');
    }

    public function destroy(string $id)
    {
        $file = $this->fileService->getTrashedFile(auth()->id(), $id);

        $this->trashService->forceDeleteFile($file);

        return redirect()->route('trash.index')->with('success', 'File permanently deleted');
    }

    public function purge()
    {
        $count = $this->trashService->purgeTrashed(auth()->id());

        return redirect()->route('trash.index')->with('success', "{$count} file(s) permanently deleted");
    }
}
