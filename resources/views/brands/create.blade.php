@extends('layout.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-4">Nueva marca</h1>

{{-- Errores de validación --}}
@if ($errors->any())
  <div class="bg-red-100 text-red-800 border border-red-300 rounded px-3 py-2 mb-3 text-sm">
    <ul class="list-disc list-inside">
      @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="{{ route('brands.store') }}" class="bg-gray-800 text-white rounded shadow p-4">
  @csrf

  {{-- Nombre --}}
  <div class="mb-4">
    <label class="block text-sm font-medium mb-1">Nombre</label>
    <input type="text" name="name"
           class="w-full rounded border-gray-300 text-gray-900 px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-500"
           value="{{ old('name') }}"
           required>
  </div>

  {{-- Activo --}}
  <div class="flex items-center mb-4">
    <input type="hidden" name="active" value="0">
    <input type="checkbox" name="active" id="active" value="1"
           class="h-4 w-4 text-indigo-600 border-gray-300 rounded"
           {{ old('active', true) ? 'checked' : '' }}>
    <label for="active" class="ml-2 text-sm">Activo</label>
  </div>

  <div class="flex gap-2">
    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded">
      Guardar
    </button>
    <a href="{{ route('brands.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded">
      Cancelar
    </a>
  </div>
</form>
@endsection
