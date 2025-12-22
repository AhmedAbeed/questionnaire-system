<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class pageHeader extends Component
{
    public $title;
    public $pageDescription;
    public $actionItems;

    /**
     * Create a new component instance.
     */
    public function __construct($title = '', $pageDescription = '', $actionItems = [])
    {
        $this->title = $title;
        $this->pageDescription = $pageDescription;
        $this->actionItems = $actionItems;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.page-header');
    }
}