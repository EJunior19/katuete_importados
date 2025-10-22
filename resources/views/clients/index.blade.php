{{-- resources/views/clients/index.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="w-full px-6">

  {{-- Título --}}
  <div class="flex items-center gap-2 mb-4">
    <h1 class="text-3xl font-bold text-green-400">👥 Lista de Clientes</h1>
  </div>

  {{-- Barra superior: botón + filtros compactos --}}
  <div class="mb-6">
    <div class="flex flex-col gap-3">

      <div class="flex justify-start">
        <a href="{{ route('clients.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">
          + Nuevo Cliente
        </a>
      </div>

      {{-- Filtros / Buscador --}}
      <form method="GET" action="{{ route('clients.index') }}"
            class="w-full bg-[#0f172a] text-white border border-green-700/30 rounded-xl p-4">
        @php
          $status  = request('status','all');
          $test    = request('test','all');
          $per     = (int) request('per_page', 25);
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
          {{-- Buscar --}}
          <div class="md:col-span-5">
            <label class="block text-sm text-green-300 mb-1">Buscar</label>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Nombre, email, teléfono, código, RUC…"
                   class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600">
          </div>

          {{-- Estado --}}
          <div class="md:col-span-2">
            <label class="block text-sm text-green-300 mb-1">Estado</label>
            <select name="status"
                    class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-600">
              <option value="all"     {{ $status==='all' ? 'selected' : '' }}>Todos</option>
              <option value="active"  {{ $status==='active' ? 'selected' : '' }}>Activos</option>
              <option value="inactive"{{ $status==='inactive' ? 'selected' : '' }}>Inactivos</option>
            </select>
          </div>

          {{-- Tipo --}}
          <div class="md:col-span-2">
            <label class="block text-sm text-green-300 mb-1">Tipo</label>
            <select name="test"
                    class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-600">
              <option value="all"  {{ $test==='all' ? 'selected' : '' }}>Todos</option>
              <option value="prod" {{ $test==='prod' ? 'selected' : '' }}>Producción</option>
              <option value="test" {{ $test==='test' ? 'selected' : '' }}>Prueba</option>
            </select>
          </div>

          {{-- Por página --}}
          <div class="md:col-span-1">
            <label class="block text-sm text-green-300 mb-1">Pág.</label>
            <select name="per_page"
                    class="w-full bg-gray-800 border border-gray-700 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-600">
              @foreach([10,25,50,100] as $n)
                <option value="{{ $n }}" {{ $per===$n ? 'selected' : '' }}>{{ $n }}</option>
              @endforeach
            </select>
          </div>

          {{-- Botones --}}
          <div class="md:col-span-2 flex gap-2">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 w-full bg-green-600 hover:bg-green-700 text-white rounded">
              🔎 Buscar
            </button>
            <a href="{{ route('clients.index') }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2 w-full bg-gray-700 hover:bg-gray-600 text-white rounded">
              ✖ Limpiar
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- Flash --}}
  @if(session('success'))
    <x-flash-message type="success" :message="session('success')" />
  @endif

  {{-- Tabla --}}
  <div class="bg-gray-900 text-white rounded-xl shadow-2xl p-0 border-2 border-green-400 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead class="bg-gray-800 text-green-300 text-base">
          <tr>
            <th class="px-6 py-3">ID</th>
            <th class="px-6 py-3">Código</th>
            <th class="px-6 py-3">Nombre</th>
            <th class="px-6 py-3">Email</th>
            <th class="px-6 py-3">Teléfono</th>
            <th class="px-6 py-3">Estado</th>
            <th class="px-6 py-3">Tipo</th>
            <th class="px-6 py-3 text-center">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-800 text-base">
          @forelse($clients as $c)
            <tr class="hover:bg-gray-800">
              <td class="px-6 py-3">{{ $c->id }}</td>
              <td class="px-6 py-3">{{ $c->code }}</td>
              <td class="px-6 py-3">
                <div class="flex flex-col">
                  <span>{{ $c->name }}</span>
                  @if(!empty($c->ruc))
                    <span class="text-xs text-gray-400">RUC: {{ $c->ruc }}</span>
                  @endif
                </div>
              </td>
              <td class="px-6 py-3">{{ $c->email }}</td>
              <td class="px-6 py-3">{{ $c->phone ?? '—' }}</td>
              <td class="px-6 py-3">
                @if((int)($c->active ?? 0) === 1)
                  <span class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-emerald-900 text-emerald-200 border border-emerald-700">Activo</span>
                @else
                  <span class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-red-900 text-red-200 border border-red-700">Inactivo</span>
                @endif
              </td>
              <td class="px-6 py-3">
                @if((int)($c->is_test ?? 0) === 1)
                  <span class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-yellow-900 text-yellow-200 border border-yellow-700">Prueba</span>
                @else
                  <span class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-blue-900 text-blue-200 border border-blue-700">Producción</span>
                @endif
              </td>
              <td class="px-6 py-3">
                <div class="flex justify-center gap-2">
                  <x-action-buttons 
                    :show="route('clients.show',$c)" 
                    :edit="route('clients.edit',$c)" 
                    :delete="route('clients.destroy',$c)" 
                    :name="'el cliente '.$c->name" />
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="px-6 py-6 text-center text-gray-400">
                No se encontraron clientes con los filtros aplicados.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Paginación --}}
    <div class="px-4 py-3 border-t border-gray-800">
      {{ $clients->appends(request()->query())->links() }}
    </div>
  </div>
</div>
@endsection
