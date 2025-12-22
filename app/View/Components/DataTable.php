<?php
namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DataTable extends Component
{
    /**
     * Create a new component instance.
     *
     * @param array $headers Table header labels
     * @param array $columns Column names for data source
     * @param array $tableData Data to display in the table
     */
    public function __construct(
        public array $headers = [],
        public array $columns = [],
        public array $tableData = [],
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.data-table');
    }
}