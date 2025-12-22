<?php

namespace App\View\Components\Layouts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TopBar extends Component
{
   


    /**
     * Create a new component instance.
     *
     * @param  array  $user  User profile data
     * @param  array|null  $notifications  Notification items
     * @param  array  $languages  Available languages
     */
    public function __construct()
    {
       
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.layouts.topbar');
    }

   

    
}
