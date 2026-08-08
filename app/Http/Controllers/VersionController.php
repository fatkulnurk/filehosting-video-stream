<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Files\VersioningServiceInterface;
use App\Http\Requests\Files\StoreVersionRequest;

class VersionController extends Controller
{
    public function __construct(
        private VersioningServiceInterface $versioningService,
        private FileServiceInterface $fileService
    ) {}

    public function store(StoreVersionRequest $request, string $id)
    {
        $file = $this->fileService->getOwnedFile(auth()->id(), $id);

        $version = $this->versioningService->createVersion(
            $file,
            $request->file('file'),
            auth()->id(),
            $request->changelog
        );

        return redirect()->back()->with('success', "Version {$version->version} saved");
    }

    public function restore(string $id, string $version)
    {
        $file = $this->fileService->getOwnedFile(auth()->id(), $id);

        $this->versioningService->restore($file, $version);

        return redirect()->back()->with('success', 'Version restored');
    }

    public function destroy(string $id, string $version)
    {
        $file = $this->fileService->getOwnedFile(auth()->id(), $id);

        $this->versioningService->delete($file, $version);

        return redirect()->back()->with('success', 'Version deleted');
    }
}
