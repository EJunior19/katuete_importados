{{-- resources/views/categories/edit.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="w-full px-6">
  <h1 class="text-3xl font-bold mb-6 text-green-400 flex items-center gap-2">
    ✏️ Editar categoría #{{ $category->id }}
  </h1>

  {{-- Mensajes flash --}}
  <x-flash-message />

  {{-- Errores de validación --}}
  @if ($errors->any())
    <div class="bg-red-900/40 border border-red-600 text-red-200 rounded-lg px-4 py-3 mb-6">
      <ul class="list-disc list-inside text-sm">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="bg-gray-900 text-white rounded-xl shadow-2xl p-8 border-2 border-green-400 w-full">
    <form method="POST" action="{{ route('categories.update', $category) }}" class="space-y-6">
      @csrf
      @method('PUT')

      {{-- Datos de solo lectura --}}
      <div class="grid md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-1">ID</label>
          <input type="text" value="{{ $category->id }}" readonly
                 class="w-full rounded-lg border border-gray-700 bg-gray-800 text-gray-200 px-3 py-2">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-300 mb-1">Código</label>
          <input type="text" value="{{ $category->code ?? '—' }}" readonly
                 class="w-full rounded-lg border border-gray-700 bg-gray-800 text-gray-200 px-3 py-2 font-mono">
        </div>
      </div>

      {{-- Nombre --}}
      <div>
        <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Nombre</label>
        <input id="name" name="name" type="text" required
               value="{{ old('name', $category->name) }}"
               class="w-full rounded-lg border border-gray-700 bg-gray-800 text-gray-100 px-3 py-2 focus:outline-none focus:ring focus:ring-green-500/40">
        <p class="text-xs text-gray-400 mt-1">Debe ser único (no distingue mayúsculas/minúsculas).</p>
      </div>

      {{-- Activo --}}
      <div class="flex items-center gap-3">
        <input type="hidden" name="active" value="0">
        <input id="active" name="active" type="checkbox" value="1"
               class="h-4 w-4 text-green-500 border-gray-700 rounded focus:ring-green-500"
               {{ old('active', $category->active) ? 'checked' : '' }}>
        <label for="active" class="text-sm text-gray-300">Activo</label>
      </div>

      {{-- Fechas --}}
      <p class="text-gray-400 text-sm">
        📅 Creado: {{ $category->created_at?->format('d/m/Y H:i') }} ·
        🔄 Actualizado: {{ $category->updated_at?->format('d/m/Y H:i') }}
      </p>

      {{-- Botones --}}
      <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit"
                class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg shadow">
          Guardar cambios
        </button>

        <a href="{{ route('categories.show', $category) }}"
           class="px-5 py-2 border border-gray-500 text-gray-300 hover:bg-gray-700 text-sm rounded-lg shadow">
          ← Volver
        </a>

        <a href="{{ route('categories.index') }}"
           class="px-5 py-2 border border-gray-500 text-gray-300 hover:bg-gray-700 text-sm rounded-lg shadow">
          Lista de categorías
        </a>
      </div>
    </form>

    {{-- Zona peligrosa (opcional) --}}
    <div class="mt-10 border-t border-gray-700 pt-6">
      <h2 class="text-lg font-semibold text-red-400 mb-3">Zona peligrosa</h2>
      <p class="text-sm text-gray-400 mb-4">Eliminar esta categoría es irreversible.</p>

      <x-action-buttons
        :delete="route('categories.destroy', $category)"
        :name="'la categoría '.$category->name"
      />
    </div>
  </div>
</div>
@endsection
