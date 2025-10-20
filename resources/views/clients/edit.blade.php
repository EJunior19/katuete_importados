{{-- resources/views/clients/edit.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="w-full px-6">
  {{-- =========================
       Header + Tabs
     ========================= --}}
  @php
    $isDocs    = request('tab') === 'docs';
    $docsCount = $client->relationLoaded('documents') ? $client->documents->count() : ($client->documents_count ?? null);
    $refsCount = $client->relationLoaded('references') ? $client->references->count() : ($client->references_count ?? null);
  @endphp

  <div class="flex items-center justify-between mb-4">
    <h1 class="text-3xl font-bold text-green-400">Editar cliente</h1>

    <div class="flex gap-2">
      {{-- Tab: Datos --}}
      <a href="{{ route('clients.edit', $client) }}"
         class="px-3 py-1.5 rounded-lg border text-sm
                {{ $isDocs ? 'border-gray-700 bg-gray-800 text-gray-300' : 'border-green-400 bg-gray-900 text-green-300' }}">
        Datos
      </a>

      {{-- Tab: Documentos/Referencias --}}
      <a href="{{ route('clients.edit', [$client, 'tab' => 'docs']) }}"
         class="px-3 py-1.5 rounded-lg border text-sm
                {{ $isDocs ? 'border-green-400 bg-gray-900 text-green-300' : 'border-gray-700 bg-gray-800 text-gray-300' }}">
        ➕ Agregar documentos
        @if(!is_null($docsCount))
          <span class="ml-2 inline-flex items-center justify-center px-1.5 py-0.5 text-xs rounded bg-gray-800 border border-gray-700">{{ $docsCount }}</span>
        @endif
        @if(!is_null($refsCount))
          <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs rounded bg-gray-800 border border-gray-700">Ref: {{ $refsCount }}</span>
        @endif
      </a>
    </div>
  </div>

  {{-- =========================
       Errores de validación
     ========================= --}}
  @if ($errors->any())
    <div class="bg-red-900/20 text-red-200 border border-red-400 rounded px-3 py-2 mb-3 text-sm">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- =========================
       FORM DATOS (solo si no es pestaña docs)
     ========================= --}}
  @if(!$isDocs)
    <form method="POST" action="{{ route('clients.update',$client) }}"
          class="bg-gray-900 text-white rounded-xl border-2 border-green-400 p-6 space-y-4">
      @csrf @method('PUT')

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs uppercase mb-1 text-gray-400">Código</label>
          <input type="text" value="{{ $client->code }}" readonly
                 class="w-full bg-gray-200 text-gray-900 px-3 py-2 rounded font-mono">
        </div>
        <div>
          <label class="block text-xs uppercase mb-1 text-gray-400">ID</label>
          <input type="text" value="{{ $client->id }}" readonly
                 class="w-full bg-gray-200 text-gray-900 px-3 py-2 rounded">
        </div>
      </div>

      <div>
        <label class="block text-xs uppercase mb-1 text-gray-400">Nombre</label>
        <input type="text" name="name" required
               value="{{ old('name',$client->name) }}"
               class="w-full rounded-lg bg-gray-800 border border-gray-700 focus:border-green-400 focus:ring-0 px-3 py-2">
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs uppercase mb-1 text-gray-400">Email</label>
          <input type="email" name="email" required
                 value="{{ old('email',$client->email) }}"
                 class="w-full rounded-lg bg-gray-800 border border-gray-700 focus:border-green-400 focus:ring-0 px-3 py-2">
        </div>
        <div>
          <label class="block text-xs uppercase mb-1 text-gray-400">Teléfono</label>
          <input type="text" name="phone"
                 value="{{ old('phone',$client->phone) }}"
                 class="w-full rounded-lg bg-gray-800 border border-gray-700 focus:border-green-400 focus:ring-0 px-3 py-2">
        </div>
      </div>

      <div>
        <label class="block text-xs uppercase mb-1 text-gray-400">Dirección</label>
        <input type="text" name="address"
               value="{{ old('address',$client->address) }}"
               class="w-full rounded-lg bg-gray-800 border border-gray-700 focus:border-green-400 focus:ring-0 px-3 py-2">
      </div>

      <div>
        <label class="block text-xs uppercase mb-1 text-gray-400">Notas</label>
        <textarea name="notes" rows="3"
                  class="w-full rounded-lg bg-gray-800 border border-gray-700 focus:border-green-400 focus:ring-0 px-3 py-2">{{ old('notes',$client->notes) }}</textarea>
      </div>

      <div class="flex items-center gap-2">
        <input type="hidden" name="active" value="0">
        <input id="active" type="checkbox" name="active" value="1"
               class="h-4 w-4 text-green-500 bg-gray-800 border-gray-700 rounded"
               {{ old('active',$client->active) ? 'checked' : '' }}>
        <label for="active" class="text-sm text-gray-300">Activo</label>
      </div>

      <div class="flex gap-2">
        <button type="submit"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded">
          Actualizar
        </button>
        <a href="{{ route('clients.show',$client) }}"
           class="px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm rounded">
          Cancelar
        </a>
        <a href="{{ route('clients.edit', [$client, 'tab' => 'docs']) }}"
           class="px-4 py-2 bg-sky-700 hover:bg-sky-800 text-white text-sm rounded">
          ➕ Agregar documentos
        </a>
      </div>
    </form>
  @endif

  {{-- =========================
       DOCUMENTOS + REFERENCIAS (solo pestaña docs)
     ========================= --}}
  @if($isDocs)
    @include('clients.partials.documents', ['client' => $client])
    @include('clients.partials.references', ['client' => $client])
  @endif
</div>
@endsection
