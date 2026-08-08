<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Directories\DirectoryServiceInterface;
use App\Contracts\Services\Directories\DirectoryShareServiceInterface;
use App\Http\Requests\Directories\ShareDirectoryRequest;
use App\Traits\AppliesTableParams;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DirectoryShareController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private DirectoryShareServiceInterface $shareService,
        private DirectoryServiceInterface $directoryService
    ) {}

    public function store(ShareDirectoryRequest $request, string $directory)
    {
        $directoryModel = $this->directoryService->getOwnedDirectory(auth()->id(), $directory);

        $targetUser = $this->shareService->findUserByEmail($request->shared_with);

        abort_if(! $targetUser, 404, 'User not found');

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
        $params['filters'] = $request->only(['permission']);

        $shares = $this->shareService->paginateSharedWithMe(auth()->id(), $params);

        return Inertia::render('Directories/SharedWithMe', [
            'shares' => $shares,
            'filters' => $this->tableFiltersWith($params, $request, ['permission']),
        ]);
    }

    public function destroy(string $id)
    {
        $this->shareService->getShareForOwner($id, auth()->id());

        $this->shareService->removeShare($id);

        return redirect()->back()->with('success', 'Share removed');
    }
}
