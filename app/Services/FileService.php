<?php

namespace App\Services;

use App\Helpers\StorageServerHelper;
use App\Models\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class FileService implements FileServiceInterface
{
    /**
     * @param Request $request
     * @param mixed $directoryID
     * @return mixed
     * @throws \Exception
     */
    public function store(Request $request, $directoryID)
    {
        if ($request->hasFile('video')) {
            $file = $request->file('video');

            $randomString = Str::random(15);
            $randomCode = Str::random(15);
            $random = $randomString . '-=-' . $randomCode . '-=-' . time();
            $randomHash = md5($random);

            $filename = $random . '.' . $file->getClientOriginalExtension();
            $fileStore = $file->storeAs(
                'videos', $filename, StorageServerHelper::getActiveKey()
            );

            $fileDatabase = File::create([
                'user_id' => Auth::id(),
                'directory_id' => $directoryID,
                'storage_server_id' => StorageServerHelper::getActiveID(),
                'code' => $randomHash . '-' . time(),
                'client_original_name' => $file->getClientOriginalName(),
                'client_original_mime_type' => $file->getClientMimeType(),
                'client_original_extension' => $file->getClientOriginalExtension(),
                'name' => $filename,
                'mime_type' => $file->getMimeType(),
                'extension' => $file->extension(),
                'path' => $fileStore,
                'size' => $file->getSize(),
            ]);

            return $fileDatabase;
        }
    }

    public function delete($fileID)
    {
        File::findOrFail($fileID)->delete();
    }
}
