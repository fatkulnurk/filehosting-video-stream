<?php

namespace App\Services\Directory;

use App\Models\Directory;
use App\Models\File;
use Illuminate\Support\Facades\Auth;

class DirectoryService
{
    public function delete(int $folderID)
    {
        $directory = Directory::with(['childrenDirectory'])
            ->findOrFail($folderID);

        $this->deleteAllFiles($directory->id);

        foreach ($directory->childrenDirectory as $dir) {
            (new DirectoryService())->delete($dir->id);
        }

        $directory->delete();
    }

    public function deleteAllFiles($directoryID)
    {
        File::where('user_id', Auth::id())->where('directory_id', $directoryID)->delete();
    }
}
