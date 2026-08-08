<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Files\BatchOperationServiceInterface;
use App\Http\Requests\Files\BatchDownloadRequest;

class BatchController extends Controller
{
    public function download(BatchDownloadRequest $request, BatchOperationServiceInterface $batchService)
    {
        $zipPath = $batchService->downloadZip($request->file_ids, auth()->id());

        return response()->download($zipPath)->deleteFileAfterSend();
    }
}
