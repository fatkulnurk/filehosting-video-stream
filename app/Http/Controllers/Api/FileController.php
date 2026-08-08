<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Files\StatsServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreFileRequest;
use App\Http\Resources\Files\FileCollection;
use App\Http\Resources\Files\FileResource;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    public function __construct(
        private FileServiceInterface $fileService,
        private StatsServiceInterface $statsService
    ) {}

    private function user(Request $request): ?User
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

        $files = $this->fileService->getPaginated(
            $userId,
            $request->directory_id,
            $request->search,
            (int) ($request->per_page ?? 20),
        );

        return new FileCollection($files);
    }

    public function store(StoreFileRequest $request)
    {
        $this->requirePermission($request, 'file.write');

        $file = $this->fileService->store(
            $request->file('file'),
            $request->directory_id,
            $this->user($request)->id,
            $request->disk
        );

        return (new FileResource($file))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id)
    {
        $this->requirePermission($request, 'file.read');

        $file = $this->fileService->getById($id);

        if (! $file || $file->user_id !== $this->user($request)->id) {
            abort(404);
        }

        return new FileResource($file->load('media'));
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
