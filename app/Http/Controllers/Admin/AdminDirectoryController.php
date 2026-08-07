<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Concerns\AppliesTableParams;
use App\Models\Directory;
use App\Models\File;
use App\Models\User;
use App\Services\Interfaces\DirectoryServiceInterface;
use App\Services\Interfaces\FileServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDirectoryController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private DirectoryServiceInterface $directoryService,
        private FileServiceInterface $fileService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $directories = $this->applyTableQuery(
            $this->applyFilters(
                Directory::query()
                    ->with(['user', 'parent'])
                    ->withCount('files')
                    ->withTrashed(),
                $request,
                [
                    'user_id' => fn ($q, $v) => $q->where('user_id', $v),
                    'status' => [
                        'active' => fn ($q) => $q->whereNull('deleted_at'),
                        'trashed' => fn ($q) => $q->onlyTrashed(),
                    ],
                ]
            ),
            $params,
            searchable: ['name'],
            sortable: [
                'name' => 'name',
                'user' => \DB::raw('(SELECT u.name FROM users u WHERE u.id = directories.user_id)'),
                'path' => 'path',
                'files' => 'files_count',
                'created_at' => 'created_at',
            ],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Admin/Directories/Index', [
            'directories' => $directories,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'filters' => $this->tableFiltersWith($params, $request, ['user_id', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'uuid', 'exists:directories,id'],
        ]);

        $this->directoryService->create(
            $request->name,
            $request->parent_id,
            $request->user_id
        );

        return redirect()->back()->with('success', 'Directory created');
    }

    public function update(Request $request, string $id)
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);

        $directory = Directory::withTrashed()->findOrFail($id);

        $this->directoryService->update($directory, $request->name);

        return redirect()->back()->with('success', 'Directory renamed');
    }

    public function destroy(string $id)
    {
        $directory = Directory::withTrashed()->findOrFail($id);

        if (! $directory->trashed()) {
            $this->directoryService->delete($directory);
        }

        return redirect()->back()->with('success', 'Directory moved to trash');
    }

    public function forceDestroy(string $id)
    {
        $directory = Directory::withTrashed()->findOrFail($id);

        $files = File::withTrashed()->where('directory_id', $directory->id)->get();

        foreach ($files as $file) {
            $this->fileService->forceDelete($file);
        }

        $directory->forceDelete();

        return redirect()->route('admin.directories.index')->with('success', 'Directory permanently deleted');
    }
}
