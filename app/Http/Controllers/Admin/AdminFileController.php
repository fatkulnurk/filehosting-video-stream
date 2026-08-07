<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Concerns\AppliesTableParams;
use App\Models\File;
use App\Models\User;
use App\Services\Interfaces\FileServiceInterface;
use App\Services\Interfaces\StatsServiceInterface;
use App\Services\Interfaces\SubtitleServiceInterface;
use App\Services\Interfaces\VersioningServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminFileController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private FileServiceInterface $fileService,
        private StatsServiceInterface $statsService,
        private SubtitleServiceInterface $subtitleService,
        private VersioningServiceInterface $versioningService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $files = $this->applyTableQuery(
            $this->applyFilters(
                File::query()
                    ->with(['user', 'media'])
                    ->withTrashed(),
                $request,
                [
                    'user_id' => fn ($q, $v) => $q->where('user_id', $v),
                    'status' => [
                        'public' => fn ($q) => $q->where('is_public', true),
                        'private' => fn ($q) => $q->where('is_public', false),
                        'trashed' => fn ($q) => $q->onlyTrashed(),
                        'active' => fn ($q) => $q->whereNull('deleted_at'),
                    ],
                    'type' => [
                        'video' => fn ($q) => $q->whereHas('media', fn ($m) => $m->where('mime_type', 'LIKE', 'video/%')),
                        'image' => fn ($q) => $q->whereHas('media', fn ($m) => $m->where('mime_type', 'LIKE', 'image/%')),
                        'document' => fn ($q) => $q->whereDoesntHave('media', fn ($m) => $m->where('mime_type', 'LIKE', 'video/%')->orWhere('mime_type', 'LIKE', 'image/%')),
                    ],
                ]
            ),
            $params,
            searchable: ['name'],
            sortable: [
                'name' => 'name',
                'user' => \DB::raw("(SELECT u.name FROM users u WHERE u.id = files.user_id)"),
                'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = files.id AND media.model_type = '".addslashes(File::class)."')"),
                'views' => 'views',
                'downloads' => 'downloads',
                'created_at' => 'created_at',
                'is_public' => 'is_public',
            ],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Admin/Files/Index', [
            'files' => $files,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'filters' => $this->tableFiltersWith($params, $request, ['user_id', 'status', 'type']),
        ]);
    }

    public function show(string $id)
    {
        $file = File::withTrashed()->with(['user', 'directory', 'checksum', 'versions'])->findOrFail($id);

        return Inertia::render('Admin/Files/Show', [
            'file' => $file,
            'streamUrl' => $file->signed_stream_url,
            'subtitles' => $this->subtitleService->getTracks($file),
            'stats' => $this->statsService->getFileStats($file),
            'versions' => $this->versioningService->getVersions($file),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $file = File::withTrashed()->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        $this->fileService->rename($file, $data['name']);

        if (array_key_exists('is_public', $data)) {
            $this->fileService->updateSettings($file, ['is_public' => (bool) $data['is_public']]);
        }

        return redirect()->back()->with('success', 'File updated');
    }

    public function destroy(string $id)
    {
        $file = File::withTrashed()->findOrFail($id);

        if (! $file->trashed()) {
            $this->fileService->delete($file);
        }

        return redirect()->back()->with('success', 'File moved to trash');
    }

    public function forceDestroy(string $id)
    {
        $file = File::withTrashed()->findOrFail($id);

        if ($file->trashed()) {
            $this->fileService->forceDelete($file);
        } else {
            $this->fileService->delete($file);
            $this->fileService->forceDelete($file->fresh());
        }

        return redirect()->route('admin.files.index')->with('success', 'File permanently deleted');
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'directory_id' => ['nullable', 'uuid', 'exists:directories,id'],
            'files' => ['required', 'array'],
            'files.*' => ['file'],
        ]);

        $userId = $request->user_id;

        $successCount = 0;

        foreach ($request->file('files', []) as $file) {
            try {
                $this->fileService->store($file, $request->directory_id, $userId);
                $successCount++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->back()->with(
            'success',
            $successCount > 0
                ? "{$successCount} file(s) uploaded for {$request->user_id}"
                : 'No files uploaded'
        );
    }

    public function directories(Request $request)
    {
        $request->validate(['user_id' => ['required', 'uuid', 'exists:users,id']]);

        $tree = app(\App\Services\Interfaces\DirectoryServiceInterface::class)->getTree($request->query('user_id'));

        $rows = [];
        $walk = function ($dirs, array $ancestors = []) use (&$walk, &$rows) {
            foreach ($dirs as $dir) {
                $rows[] = [
                    'id' => $dir->id,
                    'label' => [...$ancestors, $dir->name],
                ];
                $walk($dir->children ?? collect(), [...$ancestors, $dir->name]);
            }
        };
        $walk($tree);

        return response()->json(['directories' => $rows]);
    }
}
