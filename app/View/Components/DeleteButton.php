<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DeleteButton extends Component
{
    // Props públicas (tipadas y con defaults)
    public function __construct(
        public ?string $action = null,                 // <-- ahora OPCIONAL
        public string  $method = 'DELETE',
        public ?string $confirm = null,                // si no viene, se arma con $name
        public string  $label = 'Eliminar',
        public bool    $icon  = true,
        public string  $classes = 'px-3 py-1.5 rounded bg-red-600 hover:bg-red-700 text-white',
        public string  $name = 'este registro'
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.delete-button');
    }
}
