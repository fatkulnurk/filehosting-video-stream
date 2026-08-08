<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Files\SubtitleServiceInterface;
use App\Http\Requests\Files\StoreSubtitleRequest;
use Symfony\Component\HttpFoundation\Response;

class SubtitleController extends Controller
{
    public function __construct(
        private SubtitleServiceInterface $subtitleService,
        private FileServiceInterface $fileService
    ) {}

    public function store(StoreSubtitleRequest $request, string $id)
    {
        $file = $this->fileService->getById($id);

        if (! $file || ($file->user_id !== auth()->id() && ! auth()->user()->isAdmin())) {
            abort(403);
        }

        $uploaded = $request->file('subtitle');

        $this->subtitleService->store(
            $file,
            $uploaded,
            auth()->id(),
            $request->input('language', 'en'),
            $request->input('label', 'Subtitles'),
            $request->boolean('default')
        );

        return redirect()->back()->with('success', 'Subtitle added');
    }

    public function destroy(string $id, string $media)
    {
        $file = $this->fileService->getById($id);

        if (! $file || ($file->user_id !== auth()->id() && ! auth()->user()->isAdmin())) {
            abort(403);
        }

        $this->subtitleService->delete($file, $media);

        return redirect()->back()->with('success', 'Subtitle removed');
    }

    public function show(string $id, string $media): Response
    {
        $file = $this->fileService->getById($id);

        if (! $file || $file->trashed() || $file->isExpired()) {
            abort(404);
        }

        $isOwner = $file->user_id === auth()->id();
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        if (! $file->is_public && ! $isOwner && ! $isAdmin) {
            abort(403);
        }

        return $this->subtitleService->serve($file, $media);
    }
}
