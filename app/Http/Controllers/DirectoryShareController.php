<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AppliesTableParams;
use App\Http\Requests\ShareDirectoryRequest;
use App\Models\Directory;
use App\Models\DirectoryShare;
use App\Models\User;
use App\Services\Interfaces\DirectoryShareServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DirectoryShareController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private DirectoryShareServiceInterface $shareService
    ) {}

    public function store(ShareDirectoryRequest $request, string $directory)
    {
        $directoryModel = Directory::where('user_id', auth()->id())->findOrFail($directory);

        $targetUser = User::where('email', $request->shared_with)->first();

        $share = $this->shareService->share(
            $directoryModel,
            auth()->id(),
            $targetUser->id,
            $request->permission,
            $request->boolean('recursive', true)
        );

        return redirect()->back()->with('success', "Folder shared with {$targetUser->email}");
    }

    public function sharedWithMe(Request $request)
    {
        $params = $this->tableParams($request);

        $shares = $this->applyFilters(
            $this->applyTableQuery(
                DirectoryShare::query()
                    ->where('shared_with', auth()->id())
                    ->with('directory', 'sharedByUser', 'sharedWithUser'),
                $params,
                searchable: [],
                sortable: [
                    'dir' => \DB::raw('(SELECT d.name FROM directories d WHERE d.id = directory_shares.directory_id)'),
                    'shared_by' => \DB::raw('(SELECT u.name FROM users u WHERE u.id = directory_shares.shared_by)'),
                    'permission' => 'permission',
                    'created_at' => 'created_at',
                ],
                defaultSort: 'created_at',
                defaultDirection: 'desc',
            ),
            $request,
            ['permission' => 'permission'],
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Directories/SharedWithMe', [
            'shares' => $shares,
            'filters' => $this->tableFiltersWith($params, $request, ['permission']),
        ]);
    }

    public function destroy(string $id)
    {
        $share = \App\Models\DirectoryShare::findOrFail($id);

        if ($share->shared_by !== auth()->id() && $share->directory->user_id !== auth()->id()) {
            abort(403);
        }

        $this->shareService->removeShare($id);

        return redirect()->back()->with('success', 'Share removed');
    }
}