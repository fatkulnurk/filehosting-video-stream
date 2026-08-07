<?php

namespace App\Http\Controllers;

use App\Events\FileDownloaded;
use App\Http\Concerns\AppliesTableParams;
use App\Http\Requests\MoveRequest;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\StoreUrlRequest;
use App\Http\Requests\UpdateFileSettingsRequest;
use App\Models\Directory;
use App\Models\File;
use App\Repositories\Interfaces\DownloadTaskRepositoryInterface;
use App\Services\Interfaces\BatchOperationServiceInterface;
use App\Services\Interfaces\DirectoryServiceInterface;
use App\Services\Interfaces\DownloaderServiceInterface;
use App\Services\Interfaces\FileAccessServiceInterface;
use App\Services\Interfaces\FileFavoriteServiceInterface;
use App\Services\Interfaces\FileServiceInterface;
use App\Services\Interfaces\StatsServiceInterface;
use App\Services\Interfaces\StorageServiceInterface;
use App\Services\Interfaces\SubtitleServiceInterface;
use App\Services\Interfaces\VersioningServiceInterface;
use Illuminate\Http\Request;
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
        private VersioningServiceInterface $versioningService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);
        $directoryId = $request->input('directory');

        $files = $this->applyTableQuery(
            File::query()
                ->where('user_id', auth()->id())
                ->when($directoryId, fn ($q) => $q->where('directory_id', $directoryId))
                ->when(! $directoryId, fn ($q) => $q->whereNull('directory_id'))
                ->with('media'),
            $params,
            searchable: ['name'],
            sortable: [
                'name' => 'name',
                'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = files.id AND media.model_type = '".addslashes(File::class)."')"),
                'views' => 'views',
                'downloads' => 'downloads',
                'created_at' => 'created_at',
            ],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        $directories = Directory::where('user_id', auth()->id())
            ->when($directoryId, fn ($q) => $q->where('parent_id', $directoryId))
            ->when(! $directoryId, fn ($q) => $q->whereNull('parent_id'))
            ->withCount('files')
            ->orderBy('name')
            ->get();

        $breadcrumbs = $directoryId
            ? $this->directoryService->getBreadcrumbs($directoryId)
            : collect();

        return Inertia::render('Files/Index', [
            'files' => $files,
            'directories' => $directories,
            'currentDirectory' => $directoryId,
            'breadcrumbs' => $breadcrumbs,
            'directoryTree' => $this->directoryService->getTree(auth()->id()),
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

        $subtitleService = app(SubtitleServiceInterface::class);

        return Inertia::render('Files/Show', [
            'file' => $file->load('directory', 'checksum', 'versions', 'user'),
            'streamUrl' => $file->signed_stream_url,
            'subtitles' => $subtitleService->getTracks($file),
            'isFavorited' => $this->favoriteService->isFavorited(auth()->id(), $id),
            'stats' => $this->statsService->getFileStats($file),
            'versions' => $this->versioningService->getVersions($file),
        ]);
    }

    public function store(StoreFileRequest $request)
    {
        if ($request->hasFile('files')) {
            $results = app(BatchOperationServiceInterface::class)->storeBatch(
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
        $task = app(DownloadTaskRepositoryInterface::class)->create([
            'user_id' => auth()->id(),
            'type' => 'url',
            'source' => $request->url,
            'directory_id' => $request->directory_id,
            'disk' => $request->disk,
            'status' => 'queued',
            'progress' => 0,
        ]);

        app(DownloaderServiceInterface::class)->dispatch($task);

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

    public function rename(Request $request, string $id)
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);

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
