<?php

namespace App\View\Components;

use Illuminate\View\Component;

class GuestLayout extends Component
{
    public $title;
    public $showHero = false;

    public function __construct($title = 'Dashboard', $showHero = false)
    {
        $this->title = $title;
        $this->showHero = $showHero;
    }

    /**
     * Get the view / contents that represents the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('layouts.guest');
    }
}
