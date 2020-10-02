<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\Times\Time;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use function GuzzleHttp\Psr7\str;

class StorageUrlController extends Controller
{
    public function __invoke(Request $request)
    {
        $code = $request->query('code');
        $path = base64_decode($request->query('hash'));
        $expiredTimeHash = (string) base64_decode($request->query('key'));

        if (!(new Time())->checkExpiredTime($expiredTimeHash)) {
            abort(404);
        }

        $file = File::where('code', $code)
            ->where('path', Crypt::decrypt($path))
            ->firstOrFail();

        return Storage::disk($file->storageServer->name)
            ->download($file->path);
    }
}
