<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ActiveQuestionnaireCarousel extends Component
{
    /**
     * Create a new component instance.
     * @param array $activeQs Active deployed questionnaires
     */
    public function __construct(
        public Array $activeQs = [],
    )
    {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard.active-questionnaire-carousel');
    }
}
