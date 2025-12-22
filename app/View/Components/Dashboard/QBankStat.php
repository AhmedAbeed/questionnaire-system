<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class QBankStat extends Component
{
    /**
     * Create a new component instance.
     * @param Int $totalQuestions Total questions in the bank
     * @param Array $questionTypes Question types in the bank
     * @param Array $categories Categories in the bank
     */
    public function __construct(
        public Int $totalQuestions,
        public Array $questionTypes,
        public Array $categories,
    )
    {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard.q-bank-stat');
    }
}
