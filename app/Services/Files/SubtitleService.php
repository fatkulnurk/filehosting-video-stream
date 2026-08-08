<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Contracts\Services\Files\SubtitleServiceInterface;
use App\Models\Files\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class SubtitleService implements SubtitleServiceInterface
{
    public function store(File $file, UploadedFile $uploadedFile, string $userId, string $language, string $label, bool $isDefault): Media
    {
        if ($isDefault) {
            $this->clearDefaults($file);
        }

        $media = $file->addMedia($uploadedFile)
            ->usingFileName($uploadedFile->getClientOriginalName())
            ->withCustomProperties([
                'language' => $language,
                'label' => $label,
                'default' => $isDefault,
                'uploaded_by' => $userId,
            ])
            ->toMediaCollection('subtitles');

        return $media;
    }

    public function delete(File $file, string $mediaId): bool
    {
        $media = $file->getMedia('subtitles')->firstWhere('id', $mediaId);

        if (! $media) {
            return false;
        }

        $media->delete();

        return true;
    }

    public function getTracks(File $file): array
    {
        return $file->getMedia('subtitles')
            ->sortBy('order_column')
            ->map(fn ($media) => [
                'id' => $media->getKey(),
                'url' => route('subtitles.show', [$file->id, $media->getKey()]),
                'srclang' => $media->getCustomProperty('language', 'en'),
                'label' => $media->getCustomProperty('label', 'Subtitles'),
                'default' => (bool) $media->getCustomProperty('default', false),
            ])
            ->values()
            ->all();
    }

    public function serve(File $file, string $mediaId): Response
    {
        $media = $file->getMedia('subtitles')->firstWhere('id', $mediaId);

        if (! $media) {
            abort(404);
        }

        $disk = Storage::disk($media->disk);
        $contents = $disk->get($media->getPathRelativeToRoot());

        if ($contents === null) {
            abort(404);
        }

        $extension = Str::lower(pathinfo($media->file_name, PATHINFO_EXTENSION));

        if ($extension === 'srt') {
            $contents = $this->srtToVtt($contents);
        }

        return response($contents, 200, [
            'Content-Type' => 'text/vtt; charset=utf-8',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function clearDefaults(File $file): void
    {
        foreach ($file->getMedia('subtitles') as $media) {
            if ($media->getCustomProperty('default', false)) {
                $media->setCustomProperty('default', false);
                $media->save();
            }
        }
    }

    private function srtToVtt(string $srt): string
    {
        $srt = preg_replace('/\r\n|\r/', "\n", $srt);

        // Ensure blank line separation between cues.
        $srt = preg_replace('/\n{3,}/', "\n\n", $srt);

        // Convert SRT timestamps (00:00:10,500 --> ...) to VTT (00:00:10.500 --> ...).
        $srt = preg_replace('/(\d{2}:\d{2}:\d{2})[,.](\d{1,3})/', '$1.$2', $srt);

        return 'WEBVTT'."\n\n".ltrim($srt);
    }
}
