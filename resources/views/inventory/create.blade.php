@extends('layout.admin')
@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-emerald-400">➕ Nuevo movimiento manual</h1>
  <a href="{{ route('inventory.index') }}" 
     class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg shadow transition">
    ⬅️ Volver
  </a>
</div>

{{-- Formulario ancho --}}
<form method="POST" action="{{ route('inventory.store') }}" 
      class="bg-gray-900 p-6 rounded-xl shadow-lg space-y-5 w-full">
  @csrf

  {{-- Grid en 2 columnas (en pantallas grandes) --}}
  <div class="grid md:grid-cols-2 gap-6">

    {{-- Producto --}}
    <div>
      <label class="block text-gray-300 font-medium mb-1">Producto</label>
      <select name="product_id" 
              class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
        <option value="">— Selecciona un producto —</option>
        @foreach($products as $p)
          <option value="{{ $p->id }}">{{ $p->name }}</option>
        @endforeach
      </select>
      @error('product_id')
        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
      @enderror
    </div>

    {{-- Tipo --}}
    <div>
      <label class="block text-gray-300 font-medium mb-1">Tipo</label>
      <select name="type" 
              class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
        <option value="entrada">Entrada</option>
        <option value="salida">Salida</option>
      </select>
      @error('type')
        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
      @enderror
    </div>

    {{-- Cantidad --}}
    <div>
      <label class="block text-gray-300 font-medium mb-1">Cantidad</label>
      <input type="number" name="quantity" min="1"
             class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
             placeholder="Ej: 10">
      @error('quantity')
        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
      @enderror
    </div>

    {{-- Razón --}}
    <div>
      <label class="block text-gray-300 font-medium mb-1">Razón</label>
      <input type="text" name="reason"
             class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
             placeholder="Ej: Ajuste de stock, pérdida, ingreso manual…">
      @error('reason')
        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
      @enderror
    </div>
  </div>

  {{-- Botones --}}
  <div class="flex gap-3 pt-4">
    <button type="submit" 
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg shadow transition">
      💾 Guardar
    </button>
    <a href="{{ route('inventory.index') }}" 
       class="px-5 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg shadow transition">
      ❌ Cancelar
    </a>
  </div>
</form>

@endsection
