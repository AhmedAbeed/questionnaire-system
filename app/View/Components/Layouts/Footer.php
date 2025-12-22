<?php

namespace App\View\Components\Layouts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Footer extends Component
{
    /**
     * The site name.
     *
     * @var string
     */
    public $name;
    
    /**
     * The current year.
     *
     * @var int
     */
    public $year;

    /**
     * Create a new component instance.
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name = 'Questionnaire')
    {
        $this->name = $name;
        $this->year = date('Y');
    }



    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.layouts.footer');
    }
}
