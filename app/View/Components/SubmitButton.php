<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SubmitButton extends Component
{
    public $label;

    public function __construct($label = 'Guardar')
    {
        $this->label = $label;
    }

    public function render(): View|Closure|string
    {
        return view('components.submit-button');
    }
}
