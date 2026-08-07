<?php

namespace App\Http\Controllers;

use App\Events\FileDownloaded;
use App\Models\File;
use App\Services\Interfaces\FileAccessServiceInterface;
use App\Services\Interfaces\FileServiceInterface;
use App\Services\Interfaces\StatsServiceInterface;
use App\Services\Interfaces\SubtitleServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class PublicController extends Controller
{
    public function __construct(
        private FileServiceInterface $fileService,
        private StatsServiceInterface $statsService,
        private FileAccessServiceInterface $accessService
    ) {}

    public function show(Request $request, string $id)
    {
        $file = $this->fileService->getPublicFile($id);

        if (! $file) {
            abort(404, 'File not found or link expired');
        }

        $media = $file->getFirstMedia();

        if (! $media) {
            abort(404, 'File content not found');
        }

        $unlocked = ! $file->isPasswordProtected()
            || $request->session()->get("file_unlocked_{$id}") === true;

        if ($file->isPasswordProtected() && ! $unlocked) {
            return Inertia::render('Public/Password', [
                'file' => [
                    'id' => $file->id,
                    'name' => $media->file_name,
                    'formatted_size' => format_bytes($media->size),
                ],
                'error' => $request->session()->get('password_error'),
            ]);
        }

        $file->load('user');

        $this->statsService->recordView($file);
        $this->accessService->logAccess($id, 'view');

        $mediaUrl = null;

        if ($file->isVideo() || $file->isImage()) {
            $mediaUrl = URL::temporarySignedRoute(
                'stream.show',
                now()->addMinutes(config('filehosting.player.signed_url_ttl_minutes', 30)),
                ['id' => $id]
            );
        }

        $downloadUrl = route('public.download', $id);

        $subtitleService = app(SubtitleServiceInterface::class);

        return Inertia::render('Public/Show', [
            'file' => [
                'id' => $file->id,
                'name' => $media->file_name,
                'display_name' => $file->name,
                'size' => $media->size,
                'formatted_size' => format_bytes($media->size),
                'mime_type' => $media->mime_type,
                'extension' => pathinfo($media->file_name, PATHINFO_EXTENSION),
                'is_video' => $file->isVideo(),
                'is_image' => $file->isImage(),
                'views' => $file->views,
                'downloads' => $file->downloads,
                'created_at' => $file->created_at?->toFormattedDateString(),
                'uploaded_by' => $file->user?->name,
                'media_url' => $mediaUrl,
                'download_url' => $downloadUrl,
                'embed_code' => $file->embed_code,
            ],
            'subtitles' => $subtitleService->getTracks($file),
        ]);
    }

    public function unlock(Request $request, string $id)
    {
        $file = $this->fileService->getPublicFile($id);

        if (! $file) {
            abort(404);
        }

        $request->validate(['password' => ['required', 'string']]);

        if (! $file->isPasswordProtected() || Hash::check($request->password, $file->password)) {
            $request->session()->put("file_unlocked_{$id}", true);

            return redirect()->route('public.show', $id);
        }

        return redirect()->back()->with('password_error', 'Invalid password');
    }

    public function download(Request $request, string $id)
    {
        $file = $this->fileService->getPublicFile($id);

        if (! $file) {
            abort(404, 'File not found or link expired');
        }

        if ($file->isPasswordProtected()) {
            $unlocked = $request->session()->get("file_unlocked_{$id}") === true;

            if (! $unlocked) {
                abort(403, 'Password required');
            }
        }

        if ($file->downloadLimitReached()) {
            abort(410, 'Download limit reached');
        }

        $media = $file->getFirstMedia();

        if (! $media) {
            abort(404);
        }

        $this->fileService->incrementDownloads($file);
        $this->statsService->recordDownload($file);
        $this->accessService->logAccess($id, 'download');
        event(new FileDownloaded($file));

        return Storage::disk($media->disk)->download($media->getPathRelativeToRoot(), $media->file_name);
    }
}
