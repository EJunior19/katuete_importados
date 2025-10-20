{{-- resources/views/suppliers/edit.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="w-full px-6">

  {{-- Título --}}
  <h1 class="text-3xl font-bold mb-6 text-green-400 flex items-center gap-2">
    📝 Editar proveedor #{{ $supplier->id }}
  </h1>

  {{-- Mensajes de estado --}}
  @if (session('status'))
    <div class="mb-4 rounded border border-green-600 bg-green-900/40 text-green-200 px-4 py-3">
      {{ session('status') }}
    </div>
  @endif

  {{-- Errores de validación (bloque) --}}
  @if ($errors->any())
    <div class="mb-4 rounded border border-red-600 bg-red-900/40 text-red-200 px-4 py-3">
      <p class="font-semibold mb-1">Revisá estos campos:</p>
      <ul class="list-disc ml-5">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="bg-gray-900 text-white rounded-xl shadow-2xl p-10 border-2 border-green-400 w-full">
    <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="grid md:grid-cols-2 gap-6">
      @csrf
      @method('PUT')

      {{-- ID (solo lectura) --}}
      <div>
        <label class="block text-sm font-medium mb-1">ID</label>
        <input type="text" value="{{ $supplier->id }}" readonly
               class="w-full rounded bg-gray-800 border border-gray-700 text-gray-200 px-3 py-2">
      </div>

      {{-- Nombre --}}
      <div>
        <label class="block text-sm font-medium mb-1">Nombre <span class="text-red-400">*</span></label>
        <input type="text" name="name" value="{{ old('name', $supplier->name) }}" required
               class="w-full rounded bg-gray-800 border @error('name') border-red-500 @else border-gray-700 @enderror text-gray-200 px-3 py-2 focus:outline-none focus:ring focus:ring-green-500">
        @error('name')
          <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
        @enderror
      </div>

      {{-- RUC --}}
      <div>
        <label class="block text-sm font-medium mb-1">RUC</label>
        <input type="text" name="ruc" value="{{ old('ruc', $supplier->ruc) }}"
               class="w-full rounded bg-gray-800 border @error('ruc') border-red-500 @else border-gray-700 @enderror text-gray-200 px-3 py-2 focus:outline-none focus:ring focus:ring-green-500">
        @error('ruc')
          <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
        @enderror
      </div>

      {{-- Aviso: contactos múltiples se editan en “show” --}}
      <div class="md:col-span-2">
        <div class="bg-blue-900/40 border border-blue-600 rounded-lg px-4 py-3 text-blue-200 text-sm">
          ℹ️ Los <strong>correos</strong>, <strong>teléfonos</strong> y <strong>direcciones</strong> se gestionan desde
          el <em>detalle del proveedor</em>.
          <a class="underline hover:text-blue-100" href="{{ route('suppliers.show', $supplier) }}">Ir al detalle →</a>
        </div>
      </div>

      {{-- Notas --}}
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Notas</label>
        <textarea name="notes" rows="3"
                  class="w-full rounded bg-gray-800 border @error('notes') border-red-500 @else border-gray-700 @enderror text-gray-200 px-3 py-2 focus:outline-none focus:ring focus:ring-green-500">{{ old('notes', $supplier->notes) }}</textarea>
        @error('notes')
          <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
        @enderror
      </div>

      {{-- Activo --}}
      <div class="flex items-center md:col-span-2 mt-2">
        <input type="hidden" name="active" value="0">
        <input type="checkbox" name="active" id="active" value="1"
               class="h-4 w-4 text-green-600 border-gray-600 rounded bg-gray-800"
               {{ old('active', (bool) $supplier->active) ? 'checked' : '' }}>
        <label for="active" class="ml-2 text-sm">Activo</label>
      </div>

      {{-- Botones --}}
      <div class="flex flex-wrap gap-4 md:col-span-2 mt-6">
        <a href="{{ url()->previous() }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-600 hover:bg-gray-800">
          ← Volver
        </a>

        <a href="{{ route('suppliers.show', $supplier) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-blue-600 text-blue-200 hover:bg-blue-900/40">
          📄 Ver detalle
        </a>

        <button type="submit"
                class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-green-600 hover:bg-green-500 text-white font-semibold shadow">
          💾 Guardar cambios
        </button>
      </div>

    </form>
  </div>
</div>
@endsection
