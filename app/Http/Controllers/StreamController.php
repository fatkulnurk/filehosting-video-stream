<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Files\FileServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamController extends Controller
{
    public function __construct(
        private FileServiceInterface $fileService
    ) {}

    public function show(Request $request, string $id): BinaryFileResponse|StreamedResponse|RedirectResponse
    {
        $file = $this->fileService->getById($id);

        if (! $file || $file->trashed() || $file->isExpired()) {
            abort(404);
        }

        $isOwner = $file->user_id === auth()->id();
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        if (! $file->is_public) {
            if (! $isOwner && ! $isAdmin) {
                abort(403);
            }

            if (! $request->hasValidSignature()) {
                abort(403, 'Invalid or expired stream link');
            }
        }

        $media = $file->getFirstMedia();

        if (! $media) {
            abort(404);
        }

        $disk = Storage::disk($media->disk);

        if ($media->disk === 'local') {
            $path = $media->getPath();

            if (! file_exists($path)) {
                abort(404);
            }

            return response()->file($path, [
                'Content-Type' => $media->mime_type,
                'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
                'Accept-Ranges' => 'bytes',
            ]);
        }

        // Remote disk: try temporary URL (S3, GCS, Azure)
        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return redirect()->away($disk->temporaryUrl($media->getPathRelativeToRoot(), now()->addMinutes(5)));
            } catch (\Throwable) {
                // fall through to proxy
            }
        }

        // Proxy stream
        return $this->proxyStream($disk, $media);
    }

    private function proxyStream($disk, $media): StreamedResponse
    {
        $stream = $disk->readStream($media->getPathRelativeToRoot());

        if (! $stream) {
            abort(404);
        }

        $size = $disk->size($media->getPathRelativeToRoot());

        $response = new StreamedResponse(function () use ($stream) {
            while (! feof($stream)) {
                echo fread($stream, 8192);
                flush();
            }
            fclose($stream);
        });

        $response->headers->set('Content-Type', $media->mime_type);
        $response->headers->set('Content-Length', $size);
        $response->headers->set('Content-Disposition', 'inline; filename="'.$media->file_name.'"');
        $response->headers->set('Accept-Ranges', 'bytes');

        return $response;
    }
}
