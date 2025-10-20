<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ActionButtons extends Component
{
    public $show;
    public $edit;
    public $delete;
    public $name;

    public function __construct($show = null, $edit = null, $delete = null, $name = 'este registro')
    {
        $this->show   = $show;
        $this->edit   = $edit;
        $this->delete = $delete;
        $this->name   = $name;
    }

    public function render(): View|Closure|string
    {
        return view('components.action-buttons');
    }
}
