{{-- Botón crear nuevo --}}
@props(['route', 'text' => 'Nuevo'])

<a href="{{ $route }}" 
   class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm rounded-lg shadow hover:bg-green-700 transition">
   ➕ {{ $text }}
</a>
