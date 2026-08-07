<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\Interfaces\VersioningServiceInterface;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function __construct(
        private VersioningServiceInterface $versioningService
    ) {}

    public function store(Request $request, string $id)
    {
        $request->validate([
            'file' => ['required', 'file'],
            'changelog' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = File::where('user_id', auth()->id())->findOrFail($id);

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
        $file = File::where('user_id', auth()->id())->findOrFail($id);

        $this->versioningService->restore($file, $version);

        return redirect()->back()->with('success', 'Version restored');
    }

    public function destroy(string $id, string $version)
    {
        $file = File::where('user_id', auth()->id())->findOrFail($id);

        $this->versioningService->delete($file, $version);

        return redirect()->back()->with('success', 'Version deleted');
    }
}