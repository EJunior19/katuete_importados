<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CreateButton extends Component
{
    public $route;
    public $label;

    public function __construct($route, $label = 'Nuevo')
    {
        $this->route = $route;
        $this->label = $label;
    }

    public function render(): View|Closure|string
    {
        return view('components.create-button');
    }
}
