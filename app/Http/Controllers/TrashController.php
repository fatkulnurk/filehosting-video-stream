<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AppliesTableParams;
use App\Models\File;
use App\Services\Interfaces\TrashServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrashController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private TrashServiceInterface $trashService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $files = $this->applyTableQuery(
            File::query()
                ->where('user_id', auth()->id())
                ->onlyTrashed()
                ->with('media'),
            $params,
            searchable: ['name'],
            sortable: [
                'name' => 'name',
                'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = files.id AND media.model_type = '" . addslashes(File::class) . "')"),
                'deleted_at' => 'deleted_at',
            ],
            defaultSort: 'deleted_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Files/Trash', [
            'files' => $files,
            'empty' => $files->isEmpty(),
            'filters' => $this->tableFilters($params),
        ]);
    }

    public function restore(string $id)
    {
        $file = File::where('user_id', auth()->id())->withTrashed()->findOrFail($id);

        $this->trashService->restoreFile($file);

        return redirect()->route('trash.index')->with('success', 'File restored');
    }

    public function destroy(string $id)
    {
        $file = File::where('user_id', auth()->id())->withTrashed()->findOrFail($id);

        $this->trashService->forceDeleteFile($file);

        return redirect()->route('trash.index')->with('success', 'File permanently deleted');
    }

    public function purge()
    {
        $count = $this->trashService->purgeOlderThan(0);

        return redirect()->route('trash.index')->with('success', "{$count} file(s) permanently deleted");
    }
}