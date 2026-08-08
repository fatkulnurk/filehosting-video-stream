<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Files\FileAccessServiceInterface;
use App\Http\Requests\Files\FileAccessIndexRequest;
use App\Traits\AppliesTableParams;
use Inertia\Inertia;

class FileAccessController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private FileAccessServiceInterface $accessService
    ) {}

    public function recentlyAccessed(FileAccessIndexRequest $request)
    {
        $params = $this->tableParams($request);
        $params['sort'] = $request->getSorts();

        $files = $this->accessService->paginateForUser(auth()->id(), $params);

        return Inertia::render('Files/RecentlyAccessed', [
            'files' => $files,
            'filters' => $this->tableFilters($params),
        ]);
    }
}
