<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\StorageServerHelper;
use App\Http\Controllers\Controller;
use App\Models\Directory;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FilesController extends Controller
{
    /**
     * @var FileService
     */
    protected $fileService;

    /**
     * FilesController constructor.
     * @param FileService $fileService
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $directories = Directory::query();
        $files = File::query();
        $parrentDirectory = 0;
        $directoryName = '...';

        if ($request->has('folder_id')) {
            $directory = Directory::select('directory_parrent_id', 'name')
                ->where('user_id', Auth::id())
                ->where('id', $request->query('folder_id'))
                ->first();

            $directoryName = $directory->name;
            $parrentDirectory = $directory
                ->directory_parrent_id;

            $directories = $directories->where('directory_parrent_id', $request->query('folder_id'));
            $files = $files->where('directory_id', $request->query('folder_id'));
        } else {
            $directories = $directories->where('directory_parrent_id', null);
        }

        $files = $files
            ->where('user_id', Auth::id())
            ->where('name', 'like', '%'. $request->query('search') .'%')
            ->orderBy('id', 'desc')
            ->paginate(10);

        $directories = $directories
            ->where('user_id', Auth::id())
            ->get();

        return view('dashboard.files.index', compact(
            'directories', 'files', 'parrentDirectory', 'directoryName'
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $directories = Directory::with('childrenDirectory')
            ->where('directory_parrent_id', null)
            ->get();

        return view('dashboard.files.create', compact('directories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'video' => 'required|mimetypes:video/avi,video/mpeg,video/quicktime,video/mp4|max:10240000',
            'directory_id' => 'nullable'
        ]);

        $directoryID = Directory::where('user_id', Auth::id())->find($request->directory_id)->id ?? null;

        $fileDatabase = $this->fileService->store($request, $directoryID);

        return redirect()
            ->route('dashboard.files.index')
            ->with('success', 'Berhasil di upload')
            ->with('file', $fileDatabase);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
