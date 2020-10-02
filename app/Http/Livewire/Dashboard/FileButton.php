<?php

namespace App\Http\Livewire\Dashboard;

use Livewire\Component;

class FileButton extends Component
{
    public $file;

    public function render()
    {
        return view('livewire.dashboard.file-button');
    }
}
