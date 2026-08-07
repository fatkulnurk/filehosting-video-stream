<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\Interfaces\FileServiceInterface;
use App\Services\Interfaces\StatsServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApiFileController extends Controller
{
    public function __construct(
        private FileServiceInterface $fileService,
        private StatsServiceInterface $statsService
    ) {}

    private function user(Request $request): ?\App\Models\User
    {
        return $request->attributes->get('api_user');
    }

    private function requirePermission(Request $request, string $permission): void
    {
        $apiKey = $request->attributes->get('api_key');

        if (! $apiKey || ! $apiKey->hasPermission($permission)) {
            abort(403, 'Missing permission: '.$permission);
        }
    }

    public function index(Request $request)
    {
        $this->requirePermission($request, 'file.read');

        $userId = $this->user($request)->id;

        $files = File::where('user_id', $userId)
            ->when($request->directory_id, fn ($q) => $q->where('directory_id', $request->directory_id))
            ->when($request->search, fn ($q) => $q->where('name', 'LIKE', "%{$request->search}%"))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($files);
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, 'file.write');

        $request->validate([
            'file' => ['required', 'file', 'max:'.config('filehosting.upload.max_size', 104857600)],
            'directory_id' => ['nullable', 'uuid', 'exists:directories,id'],
            'disk' => ['nullable', 'string'],
        ]);

        $file = $this->fileService->store(
            $request->file('file'),
            $request->directory_id,
            $this->user($request)->id,
            $request->disk
        );

        return response()->json([
            'id' => $file->id,
            'name' => $file->name,
            'public_url' => $file->public_url,
            'embed_url' => $file->embed_url,
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->requirePermission($request, 'file.read');

        $file = $this->fileService->getById($id);

        if (! $file || $file->user_id !== $this->user($request)->id) {
            abort(404);
        }

        return response()->json($file->load('media'));
    }

    public function destroy(Request $request, string $id)
    {
        $this->requirePermission($request, 'file.delete');

        $file = $this->fileService->getById($id);

        if (! $file || $file->user_id !== $this->user($request)->id) {
            abort(404);
        }

        $this->fileService->delete($file);

        return response()->json(['message' => 'File deleted']);
    }

    public function download(Request $request, string $id)
    {
        $this->requirePermission($request, 'file.read');

        $file = $this->fileService->getById($id);

        if (! $file || $file->user_id !== $this->user($request)->id) {
            abort(404);
        }

        $media = $file->getFirstMedia();

        if (! $media) {
            abort(404);
        }

        $this->fileService->incrementDownloads($file);
        $this->statsService->recordDownload($file);

        return Storage::disk($media->disk)->download($media->getPathRelativeToRoot(), $media->file_name);
    }
}
