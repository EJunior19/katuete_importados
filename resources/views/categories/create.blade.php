{{-- resources/views/categories/create.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="w-full px-6">
  <h1 class="text-3xl font-bold mb-6 text-green-400 flex items-center gap-2">
    ➕ Nueva Categoría
  </h1>

  {{-- Mensajes de error --}}
  @if ($errors->any())
    <div class="bg-red-900/60 border border-red-500 text-red-300 rounded-xl px-4 py-3 mb-4">
      <ul class="list-disc list-inside text-sm">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('categories.store') }}" 
        class="bg-gray-900 text-white rounded-xl shadow-2xl p-8 border-2 border-green-400">
    @csrf

    {{-- Nombre --}}
    <div class="mb-6">
      <label class="block text-sm font-semibold text-green-300 mb-2">Nombre</label>
      <input type="text" name="name" value="{{ old('name') }}" required
             class="w-full rounded-lg border-gray-600 bg-gray-800 text-gray-100 px-3 py-2 focus:outline-none focus:ring focus:ring-green-500">
      <p class="text-xs text-gray-400 mt-1">Debe ser único.</p>
    </div>

    {{-- Activo --}}
    <div class="flex items-center mb-6">
      <input type="hidden" name="active" value="0">
      <input type="checkbox" name="active" id="active" value="1"
             class="h-4 w-4 text-green-500 border-gray-600 rounded"
             {{ old('active', true) ? 'checked' : '' }}>
      <label for="active" class="ml-2 text-sm">Activo</label>
    </div>

    <div class="flex flex-wrap gap-3">
      <button type="submit" 
              class="px-6 py-2 rounded-lg bg-green-600 hover:bg-green-700 font-semibold shadow">
        Guardar
      </button>
      <a href="{{ route('categories.index') }}" 
         class="px-6 py-2 rounded-lg bg-gray-600 hover:bg-gray-700 font-semibold shadow">
         Cancelar
      </a>
    </div>
  </form>
</div>
@endsection
