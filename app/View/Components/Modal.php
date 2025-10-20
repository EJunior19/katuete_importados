<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Modal extends Component
{
    public $id;
    public $title;

    public function __construct($id = 'modal', $title = null)
    {
        $this->id = $id;
        $this->title = $title;
    }

    public function render(): View|Closure|string
    {
        return view('components.modal');
    }
}
