<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Files\SubtitleServiceInterface;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class EmbedController extends Controller
{
    public function __construct(
        private FileServiceInterface $fileService,
        private SubtitleServiceInterface $subtitleService
    ) {}

    public function show(string $id)
    {
        $file = $this->fileService->getPublicFile($id);

        if (! $file) {
            abort(404);
        }

        $media = $file->getFirstMedia();

        if (! $media) {
            abort(404);
        }

        if ($file->isPasswordProtected()) {
            abort(403, 'Password protected file cannot be embedded');
        }

        return Inertia::render('Public/Embed', [
            'file' => [
                'id' => $file->id,
                'name' => $media->file_name,
                'is_video' => $file->isVideo(),
                'is_image' => $file->isImage(),
                'mime_type' => $media->mime_type,
                'stream_url' => URL::temporarySignedRoute(
                    'stream.show',
                    now()->addMinutes(config('filehosting.player.signed_url_ttl_minutes', 30)),
                    ['id' => $id]
                ),
                'is_public' => $file->is_public,
            ],
            'subtitles' => $subtitleService->getTracks($file),
        ]);
    }
}
