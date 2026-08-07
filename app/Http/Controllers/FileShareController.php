<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AppliesTableParams;
use App\Http\Requests\ShareFileRequest;
use App\Models\File;
use App\Models\FileShare;
use App\Models\User;
use App\Services\Interfaces\FileShareServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FileShareController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private FileShareServiceInterface $shareService
    ) {}

    public function store(ShareFileRequest $request, string $id)
    {
        $file = File::where('user_id', auth()->id())->findOrFail($id);

        $targetUser = User::where('email', $request->shared_with)->first();

        $share = $this->shareService->share(
            $file,
            auth()->id(),
            $targetUser->id,
            $request->permission,
            $request->password,
            $request->expires_at,
            $request->download_limit
        );

        return redirect()->back()->with('success', "File shared with {$targetUser->email}");
    }

    public function sharedWithMe(Request $request)
    {
        $params = $this->tableParams($request);

        $shares = $this->applyFilters(
            $this->applyTableQuery(
                FileShare::query()
                    ->where('shared_with', auth()->id())
                    ->with('file.media', 'sharedWithUser', 'sharedByUser'),
                $params,
                searchable: [],
                sortable: [
                    'file' => \DB::raw('(SELECT f.name FROM files f WHERE f.id = file_shares.file_id)'),
                    'shared_by' => \DB::raw('(SELECT u.name FROM users u WHERE u.id = file_shares.shared_by)'),
                    'permission' => 'permission',
                    'expires' => 'expires_at',
                    'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = file_shares.file_id AND media.model_type = '" . addslashes(\App\Models\File::class) . "')"),
                    'created_at' => 'created_at',
                ],
                defaultSort: 'created_at',
                defaultDirection: 'desc',
            ),
            $request,
            ['permission' => 'permission'],
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Files/SharedWithMe', [
            'shares' => $shares,
            'filters' => $this->tableFiltersWith($params, $request, ['permission']),
        ]);
    }

    public function sharedByMe(Request $request)
    {
        $params = $this->tableParams($request);

$shares = $this->applyFilters(
            $this->applyTableQuery(
                FileShare::query()
                    ->where('shared_by', auth()->id())
                    ->with('file.media', 'sharedWithUser', 'sharedByUser'),
                $params,
                searchable: [],
                sortable: [
                    'file' => \DB::raw('(SELECT f.name FROM files f WHERE f.id = file_shares.file_id)'),
                    'shared_with' => \DB::raw('(SELECT u.name FROM users u WHERE u.id = file_shares.shared_with)'),
                    'permission' => 'permission',
                    'expires' => 'expires_at',
                    'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = file_shares.file_id AND media.model_type = '" . addslashes(\App\Models\File::class) . "')"),
                    'created_at' => 'created_at',
                ],
                defaultSort: 'created_at',
                defaultDirection: 'desc',
            ),
            $request,
            ['permission' => 'permission'],
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Files/SharedByMe', [
            'shares' => $shares,
            'filters' => $this->tableFiltersWith($params, $request, ['permission']),
        ]);
    }

    public function destroy(string $id)
    {
        $share = \App\Models\FileShare::findOrFail($id);

        if ($share->shared_by !== auth()->id() && $share->file->user_id !== auth()->id()) {
            abort(403);
        }

        $this->shareService->removeShare($id);

        return redirect()->back()->with('success', 'Share removed');
    }
}