<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Files\FileShareServiceInterface;
use App\Http\Requests\Files\ShareFileRequest;
use App\Traits\AppliesTableParams;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FileShareController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private FileShareServiceInterface $shareService,
        private FileServiceInterface $fileService
    ) {}

    public function store(ShareFileRequest $request, string $id)
    {
        $file = $this->fileService->getOwnedFile(auth()->id(), $id);

        $targetUser = $this->shareService->findUserByEmail($request->shared_with);

        abort_if(! $targetUser, 404, 'User not found');

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
        $params['filters'] = $request->only(['permission']);

        $shares = $this->shareService->paginateSharedWithMe(auth()->id(), $params);

        return Inertia::render('Files/SharedWithMe', [
            'shares' => $shares,
            'filters' => $this->tableFiltersWith($params, $request, ['permission']),
        ]);
    }

    public function sharedByMe(Request $request)
    {
        $params = $this->tableParams($request);
        $params['filters'] = $request->only(['permission']);

        $shares = $this->shareService->paginateSharedByMe(auth()->id(), $params);

        return Inertia::render('Files/SharedByMe', [
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
