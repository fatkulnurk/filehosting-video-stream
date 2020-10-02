<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\Times\Time;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class ShowController extends Controller
{
    public function show($code, Request $request)
    {
        $file = File::where('code', $code)->firstOrFail();

        return view('pages.show.index', compact('file'));
    }

    public function download(Request $request)
    {
        $code = $request->input('code');
        $path = base64_decode($request->input('hash'));
        $expiredTimeHash = (string) base64_decode($request->input('key'));

        if (!(new Time())->checkExpiredTime($expiredTimeHash)) {
            abort(404);
        }

        $file = File::where('code', $code)
            ->where('path', Crypt::decrypt($path))
            ->firstOrFail();

        return Storage::disk($file->storageServer->name)
            ->download($file->path, config('app.name') . '-' . $file->client_original_name);
    }
}
