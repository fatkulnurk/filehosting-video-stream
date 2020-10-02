<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DirectoryStore;
use App\Models\Directory;
use App\Services\Directory\DirectoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function edit(Request $request)
    {
        $request->validate([
            'directories' => 'required'
        ]);

        $directories = Directory::whereIn('id', $request->directories)->get();

        return view('dashboard.directory.edit', compact('directories'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'directories' => 'required'
        ]);

        foreach ($request->directories as $value) {
            foreach ($value as $id => $item) {
                $directory = Directory::where('user_id', Auth::id())
                    ->where('id', $id)
                    ->firstOrFail()->update(
                        [
                            'name' => $item
                        ]
                    );
            }
        }

        return redirect()
            ->route('dashboard.files.index')
            ->with('success', 'All folder update successfully');
    }

    public function delete(Request $request)
    {
        $request->validate([
            'directories' => 'required'
        ]);

        foreach ($request->directories as $directoryID) {
            (new DirectoryService())->delete($directoryID);
        }

        return redirect()
            ->route('dashboard.files.index')
            ->with('success', 'All folder selected delete successfully');
    }
}
