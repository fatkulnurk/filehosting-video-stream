<?php

namespace App\Http\Controllers;

use App\Models\File;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class EmbedController extends Controller
{
    public function show($code, Request $request)
    {
        $file = File::where('code', $code)->firstOrFail();

        return view('pages.embed.index', compact('file'));
    }
}
