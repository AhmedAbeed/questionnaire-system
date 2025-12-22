<?php

namespace App\View\Components\Layouts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Breadcrumbbar extends Component
{
    /**
     * The page title.
     *
     * @var string
     */
    public $title;

    /**
     * The breadcrumb items.
     *
     * @var array
     */
    public $breadcrumbs;

   
    /**
     * Create a new component instance.
     */
    public function __construct($title = '', $breadcrumbs = [])
    {
        $this->title = $title;
        $this->breadcrumbs = $breadcrumbs;
        
        // If no breadcrumbs are provided, generate default ones based on the current route
        if (empty($breadcrumbs)) {
            $this->breadcrumbs = $this->generateDefaultBreadcrumbs();
        }
        
        
    }


    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.layouts.breadcrumbbar');
    }

    /**
     * Generate default breadcrumbs based on the current route.
     *
     * @return array
     */
    protected function generateDefaultBreadcrumbs()
    {
        $segments = request()->segments();
        $breadcrumbs = [
            [
                'name' => 'Home',
                'url' => url('/'),
                'active' => false
            ]
        ];
        
        if (count($segments) > 0) {
            $breadcrumbs[] = [
                'name' => 'Dashboard',
                'url' => url('/dashboard'),
                'active' => false
            ];
            
            // Last segment as active breadcrumb
            if (end($segments)) {
                $breadcrumbs[] = [
                    'name' => ucfirst(str_replace('-', ' ', end($segments))),
                    'url' => '#',
                    'active' => true
                ];
            }
        }
        
        return $breadcrumbs;
    }
}
