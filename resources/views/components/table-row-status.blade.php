{{-- Badge de estado en tablas --}}
@props(['active' => true, 'yes' => 'Sí', 'no' => 'No'])

<span class="px-2 py-1 text-xs font-semibold rounded-full {{ $active ? 'bg-green-600 text-white' : 'bg-gray-500 text-gray-100' }}">
  {{ $active ? $yes : $no }}
</span>
