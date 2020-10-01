<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DirectoryStore;
use App\Models\Directory;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard.files.index');
    }

    public function store(DirectoryStore $request)
    {
        $directory = Directory::create([
            'user_id' => auth()->id(),
            'name' => $request->folder_name,
            'directory_parrent_id' => $request->has('folder_id') ? $request->folder_id : null
        ]);

        return redirect()->route('dashboard.files.index', [
            'folder_id' => $request->has('folder_id') ? $request->folder_id : null,
            'success' => true
        ])->with('success', 'Success create a new folder');
    }
}
