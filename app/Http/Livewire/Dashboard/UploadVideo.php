<?php

namespace App\Http\Livewire\Dashboard;

use App\Helpers\StorageServerHelper;
use Livewire\Component;
use Livewire\WithFileUploads;

class UploadVideo extends Component
{
    use WithFileUploads;

    public $photo;

    public function save()
    {
        $this->validate([
            'photo' => 'max:1024', // 1MB Max
        ]);

        $this->photo->store('photos');
    }
}
