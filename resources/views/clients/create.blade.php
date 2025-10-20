@extends('layout.admin')
@section('content')
<h1 class="text-2xl font-semibold mb-4 text-green-400">➕ Nuevo cliente</h1>

@if ($errors->any())
  <div class="bg-red-100 text-red-800 border border-red-300 rounded px-3 py-2 mb-3 text-sm">
    <ul class="list-disc list-inside">
      @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="{{ route('clients.store') }}" 
      class="bg-gray-900 text-white rounded shadow p-6 space-y-5 border border-green-400">
  @csrf

  {{-- Nombre --}}
  <div>
    <label class="block text-sm font-medium mb-1 text-green-300">Nombre</label>
    <input type="text" name="name" value="{{ old('name') }}"
           class="w-full rounded border border-green-400 bg-gray-800 text-white px-3 py-2 
                  focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
  </div>

  {{-- RUC / Cédula --}}
  <div>
    <label class="block text-sm font-medium mb-1 text-green-300">RUC / Cédula</label>
    <input type="text" name="ruc" value="{{ old('ruc') }}"
           class="w-full rounded border border-green-400 bg-gray-800 text-white px-3 py-2
                  focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
  </div>

  {{-- Email y Teléfono --}}
  <div class="grid md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium mb-1 text-green-300">Email</label>
      <input type="email" name="email" value="{{ old('email') }}"
             class="w-full rounded border border-green-400 bg-gray-800 text-white px-3 py-2
                    focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" required>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1 text-green-300">Teléfono</label>
      <input type="text" name="phone" value="{{ old('phone') }}"
             class="w-full rounded border border-green-400 bg-gray-800 text-white px-3 py-2
                    focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
    </div>
  </div>

  {{-- Dirección --}}
  <div>
    <label class="block text-sm font-medium mb-1 text-green-300">Dirección</label>
    <input type="text" name="address" value="{{ old('address') }}"
           class="w-full rounded border border-green-400 bg-gray-800 text-white px-3 py-2
                  focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
  </div>

  {{-- Notas --}}
  <div>
    <label class="block text-sm font-medium mb-1 text-green-300">Notas</label>
    <textarea name="notes" rows="3"
              class="w-full rounded border border-green-400 bg-gray-800 text-white px-3 py-2
                     focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">{{ old('notes') }}</textarea>
  </div>

  {{-- Activo --}}
  <div class="flex items-center">
    <input type="hidden" name="active" value="0">
    <input type="checkbox" name="active" id="active" value="1"
           class="h-4 w-4 text-green-500 border-green-400 rounded focus:ring-green-600"
           {{ old('active', true) ? 'checked' : '' }}>
    <label for="active" class="ml-2 text-sm text-green-200">Activo</label>
  </div>

  {{-- Botones --}}
  <div class="mt-6 flex gap-2">
    <button type="submit" name="action" value="save"
            class="px-4 py-2 rounded-lg bg-green-500/20 border border-green-400 text-green-300 hover:bg-green-500/30 text-sm">
      Guardar
    </button>

    <button type="submit" name="action" value="save_docs"
            class="px-4 py-2 rounded-lg bg-sky-500/20 border border-sky-400 text-sky-300 hover:bg-sky-500/30 text-sm">
      Guardar + documentos
    </button>

    <a href="{{ route('clients.index') }}"
      class="px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-gray-300 hover:bg-gray-700 text-sm">
      Cancelar
    </a>
  </div>

</form>
@endsection
