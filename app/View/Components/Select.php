<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Select extends Component
{
    public $name;
    public $label;
    public $options;
    public $selected;

    public function __construct($name, $label = null, $options = [], $selected = null)
    {
        $this->name     = $name;
        $this->label    = $label;
        $this->options  = $options;
        $this->selected = $selected;
    }

    public function render(): View|Closure|string
    {
        return view('components.select');
    }
}
