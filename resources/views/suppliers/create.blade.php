@extends('layout.admin')

@section('content')
<div class="w-full px-6">
  <h1 class="text-3xl font-bold mb-6 text-green-400">➕ Nuevo proveedor</h1>

  @if ($errors->any())
    <div class="bg-red-900/60 border border-red-500 text-red-300 rounded-xl px-4 py-3 mb-4">
      <ul class="list-disc list-inside text-sm">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('suppliers.store') }}"
        class="bg-gray-900 text-white rounded-xl shadow-2xl p-8 border-2 border-green-400">
    @csrf

    <div class="grid md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-semibold text-green-300 mb-1">Nombre</label>
        <input type="text" name="name" value="{{ old('name') }}" required
               class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2 focus:ring focus:ring-green-500/40">
      </div>

      <div>
        <label class="block text-sm font-semibold text-green-300 mb-1">RUC</label>
        <input type="text" name="ruc" value="{{ old('ruc') }}"
               class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2">
      </div>

      {{-- Nota: emails/teléfonos/direcciones se cargan en el detalle --}}
      <div class="md:col-span-2">
        <div class="bg-blue-900/40 border border-blue-600 rounded-lg px-4 py-3 text-blue-200 text-sm">
          ℹ️ Los <strong>correos</strong>, <strong>teléfonos</strong> y <strong>direcciones</strong> se gestionan desde
          el <em>detalle del proveedor</em> una vez creado.
        </div>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-green-300 mb-1">Notas</label>
        <textarea name="notes" rows="3"
               class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2">{{ old('notes') }}</textarea>
      </div>

      <div class="flex items-center gap-3 md:col-span-2">
        <input type="hidden" name="active" value="0">
        <input id="active" type="checkbox" name="active" value="1"
               class="h-4 w-4 text-green-500 border-gray-700 rounded"
               {{ old('active', true) ? 'checked' : '' }}>
        <label for="active" class="text-sm text-gray-300">Activo</label>
      </div>
    </div>

    <div class="flex flex-wrap gap-3 mt-6">
      <button type="submit" class="px-6 py-2 rounded-lg bg-green-600 hover:bg-green-700 font-semibold shadow">
        Guardar
      </button>
      <a href="{{ route('suppliers.index') }}"
         class="px-6 py-2 rounded-lg bg-gray-600 hover:bg-gray-700 font-semibold shadow">
         Cancelar
      </a>
    </div>
  </form>
</div>
@endsection
