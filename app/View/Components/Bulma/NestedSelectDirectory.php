<?php

namespace App\View\Components\Bulma;

use Illuminate\View\Component;

class NestedSelectDirectory extends Component
{
    public $directories;
    public $fromRoot;
    public $parrentDirectoryName;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($directories, $fromRoot, $parrentDirectoryName)
    {
        if (blank(session('select_from_root', ''))) {
            session(['select_from_root' => '']);
            $lastFromRoot = '';
        } else {
            $lastFromRoot = session()->get('select_from_root');
            session(['select_from_root', $lastFromRoot . '/' . $parrentDirectoryName]);
        }

        $this->directories = $directories;
        $this->fromRoot = session()->get('select_from_root');
        $this->parrentDirectoryName = $parrentDirectoryName;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.bulma.nested-select-directory');
    }

    /**
     * Determine if the given option is the current selected option.
     *
     * @param  string  $option
     * @return bool
     */
    public function isSelected($option)
    {
        return $option == request()->query('folder_id');
    }
}
