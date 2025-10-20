{{-- Tarjeta contenedor --}}
<div {{ $attributes->merge(['class' => 'bg-gray-900 rounded-xl shadow-md border border-gray-700 p-4']) }}>
    {{ $slot }}
</div>
