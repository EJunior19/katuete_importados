<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InputText extends Component
{
    public $name;
    public $label;
    public $value;
    public $type;

    public function __construct($name, $label = null, $value = '', $type = 'text')
    {
        $this->name  = $name;
        $this->label = $label;
        $this->value = $value;
        $this->type  = $type;
    }

    public function render(): View|Closure|string
    {
        return view('components.input-text');
    }
}
