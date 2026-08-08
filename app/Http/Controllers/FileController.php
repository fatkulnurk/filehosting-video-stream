<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Directories\DirectoryServiceInterface;
use App\Contracts\Services\Downloads\DownloaderServiceInterface;
use App\Contracts\Services\Files\BatchOperationServiceInterface;
use App\Contracts\Services\Files\FileAccessServiceInterface;
use App\Contracts\Services\Files\FileFavoriteServiceInterface;
use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Files\StatsServiceInterface;
use App\Contracts\Services\Files\SubtitleServiceInterface;
use App\Contracts\Services\Files\VersioningServiceInterface;
use App\Contracts\Services\Storage\StorageServiceInterface;
use App\Enums\DownloadTaskStatus;
use App\Events\Files\FileDownloaded;
use App\Http\Requests\Files\MoveRequest;
use App\Http\Requests\Files\RenameFileRequest;
use App\Http\Requests\Files\StoreFileRequest;
use App\Http\Requests\Files\StoreUrlRequest;
use App\Http\Requests\Files\UpdateFileSettingsRequest;
use App\Traits\AppliesTableParams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FileController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private FileServiceInterface $fileService,
        private DirectoryServiceInterface $directoryService,
        private StorageServiceInterface $storageService,
        private StatsServiceInterface $statsService,
        private FileFavoriteServiceInterface $favoriteService,
        private FileAccessServiceInterface $accessService,
        private VersioningServiceInterface $versioningService,
        private DownloaderServiceInterface $downloaderService,
        private SubtitleServiceInterface $subtitleService,
        private BatchOperationServiceInterface $batchService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);
        $directoryId = $request->input('directory');
        $userId = auth()->id();

        [$files, $directories, $breadcrumbs, $directoryTree] = Concurrency::run([
            fn () => $this->fileService->paginateForDirectory($userId, $directoryId, $params),
            fn () => $this->directoryService->getChildDirectories($userId, $directoryId),
            fn () => $directoryId ? $this->directoryService->getBreadcrumbs($directoryId) : collect(),
            fn () => $this->directoryService->getTree($userId),
        ]);

        return Inertia::render('Files/Index', [
            'files' => $files,
            'directories' => $directories,
            'currentDirectory' => $directoryId,
            'breadcrumbs' => $breadcrumbs,
            'directoryTree' => $directoryTree,
            'storageQuota' => [
                'used' => auth()->user()->storageUsed(),
                'limit' => auth()->user()->storageLimit(),
            ],
            'filters' => $this->tableFilters($params),
        ]);
    }

    public function show(string $id)
    {
        $file = $this->fileService->getById($id);

        if (! $file || ($file->user_id !== auth()->id() && ! auth()->user()->isAdmin())) {
            abort(403);
        }

        $this->accessService->logAccess($id, 'view');

        [$subtitles, $isFavorited, $stats, $versions] = Concurrency::run([
            fn () => $this->subtitleService->getTracks($file),
            fn () => $this->favoriteService->isFavorited(auth()->id(), $id),
            fn () => $this->statsService->getFileStats($file),
            fn () => $this->versioningService->getVersions($file),
        ]);

        return Inertia::render('Files/Show', [
            'file' => $file->load('directory', 'checksum', 'versions', 'user'),
            'streamUrl' => $file->signed_stream_url,
            'subtitles' => $subtitles,
            'isFavorited' => $isFavorited,
            'stats' => $stats,
            'versions' => $versions,
        ]);
    }

    public function store(StoreFileRequest $request)
    {
        if ($request->hasFile('files')) {
            $results = $this->batchService->storeBatch(
                $request->file('files'),
                $request->directory_id,
                auth()->id()
            );

            $successCount = count(array_filter($results, fn ($r) => $r['success']));

            return redirect()->back()->with('success', "{$successCount} file(s) uploaded");
        }

        $file = $this->fileService->store(
            $request->file('file'),
            $request->directory_id,
            auth()->id(),
            $request->disk
        );

        return redirect()->back()->with('success', "File '{$file->name}' uploaded");
    }

    public function storeFromUrl(StoreUrlRequest $request)
    {
        $task = $this->downloaderService->createTask([
            'user_id' => auth()->id(),
            'type' => 'url',
            'source' => $request->url,
            'directory_id' => $request->directory_id,
            'disk' => $request->disk,
            'status' => DownloadTaskStatus::Queued,
            'progress' => 0,
        ]);

        $this->downloaderService->dispatch($task);

        return redirect()->route('cloud-downloads.index')->with('success', 'Download from URL is queued.');
    }

    public function update(UpdateFileSettingsRequest $request, string $id)
    {
        $file = $this->fileService->getById($id);

        if (! $file || $file->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->safe()->only(['name', 'is_public', 'expires_at', 'download_limit']);

        if ($request->has('password')) {
            $data['password'] = $request->filled('password')
                ? Hash::make($request->password)
                : null;
        }

        $this->fileService->updateSettings($file, $data);

        return redirect()->back()->with('success', 'File updated');
    }

    public function rename(RenameFileRequest $request, string $id)
    {
        $file = $this->fileService->getById($id);

        if (! $file || $file->user_id !== auth()->id()) {
            abort(403);
        }

        $this->fileService->rename($file, $request->name);

        return redirect()->back()->with('success', 'File renamed');
    }

    public function move(MoveRequest $request, string $id)
    {
        $file = $this->fileService->getById($id);

        if (! $file || $file->user_id !== auth()->id()) {
            abort(403);
        }

        if ($request->filled('directory_id')) {
            $target = $this->directoryService->getBreadcrumbs($request->directory_id);

            if ($target->isNotEmpty() && $target->first()->user_id !== auth()->id()) {
                abort(403);
            }
        }

        $this->fileService->move($file, $request->directory_id);

        return redirect()->back()->with('success', 'File moved');
    }

    public function destroy(string $id)
    {
        $file = $this->fileService->getById($id);

        if (! $file || $file->user_id !== auth()->id()) {
            abort(403);
        }

        $this->fileService->delete($file);

        return redirect()->route('files.index')->with('success', 'File moved to trash');
    }

    public function download(string $id)
    {
        $file = $this->fileService->getById($id);

        if (! $file || ($file->user_id !== auth()->id() && ! auth()->user()->isAdmin())) {
            abort(403);
        }

        $media = $file->getFirstMedia();

        if (! $media) {
            abort(404);
        }

        $this->fileService->incrementDownloads($file);
        $this->statsService->recordDownload($file);
        $this->accessService->logAccess($id, 'download');
        event(new FileDownloaded($file));

        $disk = Storage::disk($media->disk);

        return $disk->download($media->getPathRelativeToRoot(), $media->file_name);
    }
}
