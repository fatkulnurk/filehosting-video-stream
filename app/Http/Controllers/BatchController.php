<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\BatchOperationServiceInterface;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function download(Request $request, BatchOperationServiceInterface $batchService)
    {
        $request->validate([
            'file_ids' => ['required', 'array', 'min:1'],
            'file_ids.*' => ['uuid', 'exists:files,id'],
        ]);

        $zipPath = $batchService->downloadZip($request->file_ids, auth()->id());

        return response()->download($zipPath)->deleteFileAfterSend();
    }
}