@props([
  'name' => 'estado',
  'value' => null,
  'options' => [
    'pendiente' => 'Pendiente',
    'aprobado' => 'Aprobado',
    'rechazado' => 'Rechazado',
  ]
])

<div class="flex items-center gap-2">
  <select name="{{ $name }}" class="bg-gray-700 text-white rounded px-2 py-1">
    @foreach($options as $k => $label)
      <option value="{{ $k }}" {{ $value === $k ? 'selected' : '' }}>
        {{ $label }}
      </option>
    @endforeach
  </select>
  <button class="px-3 py-1 bg-emerald-600 text-white rounded hover:bg-emerald-700">
    Guardar
  </button>
</div>
