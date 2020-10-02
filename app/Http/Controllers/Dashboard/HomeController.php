<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\SizeHelper;
use App\Http\Controllers\Controller;
use App\Models\Directory;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __invoke()
    {
        $directory = Directory::where('user_id', Auth::id())->count();
        $file = File::where('user_id', Auth::id())->count();
        $size = File::where('user_id', Auth::id())
            ->sum('size');
        $sizeFormat = (new SizeHelper())->convert($size);

        return view('dashboard', compact('directory', 'file', 'size', 'sizeFormat'));
    }
}
