{{-- Input de texto con estilos --}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => ''])

<div class="mb-4">
  @if($label)
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-300 mb-1">{{ $label }}</label>
  @endif
  <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $value) }}"
         {{ $attributes->merge(['class' => 'w-full rounded-lg border-gray-600 bg-gray-800 text-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-emerald-600 focus:outline-none']) }}>
  @error($name)
    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
  @enderror
</div>
