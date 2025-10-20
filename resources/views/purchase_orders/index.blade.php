{{-- resources/views/purchase_orders/index.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="w-full px-6 text-gray-200">

  {{-- Header --}}
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold text-green-400">🧾 Órdenes de compra</h1>
    <a href="{{ route('purchase_orders.create') }}"
       class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 font-semibold shadow">
      ➕ Nueva OC
    </a>
  </div>

  <x-flash-message />

  {{-- Filtros --}}
  <form method="GET" action="{{ route('purchase_orders.index') }}"
        class="bg-gray-900 border border-gray-700 rounded-xl p-4 mb-4">
    <div class="grid md:grid-cols-5 gap-3">
      {{-- Proveedor --}}
      <div>
        <label class="block text-xs text-gray-400 mb-1">Proveedor</label>
        <select name="supplier_id"
                class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2">
          <option value="">Todos</option>
          @foreach($suppliers as $s)
            <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
          @endforeach
        </select>
      </div>

      {{-- Estado --}}
      <div>
        <label class="block text-xs text-gray-400 mb-1">Estado</label>
        @php $st = request('status'); @endphp
        <select name="status"
                class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2">
          <option value="">Todos</option>
          @foreach(['borrador','enviado','recibido','cerrado'] as $opt)
            <option value="{{ $opt }}" @selected($st === $opt)>{{ ucfirst($opt) }}</option>
          @endforeach
        </select>
      </div>

      {{-- Desde --}}
      <div>
        <label class="block text-xs text-gray-400 mb-1">Desde</label>
        <input type="date" name="from" value="{{ request('from') }}"
               class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2">
      </div>

      {{-- Hasta --}}
      <div>
        <label class="block text-xs text-gray-400 mb-1">Hasta</label>
        <input type="date" name="to" value="{{ request('to') }}"
               class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2">
      </div>

      {{-- Buscar --}}
      <div>
        <label class="block text-xs text-gray-400 mb-1">Buscar</label>
        <input type="text" name="search" placeholder="N° OC o proveedor" value="{{ request('search') }}"
               class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2">
      </div>
    </div>

    <div class="mt-3 flex items-center gap-3">
      <button class="px-4 py-2 rounded-lg bg-sky-600 hover:bg-sky-700 font-semibold shadow">
        Filtrar
      </button>
      @if(request()->hasAny(['supplier_id','status','from','to','search']) &&
          filled(request('supplier_id')) || filled(request('status')) || filled(request('from')) || filled(request('to')) || filled(request('search')))
        <a href="{{ route('purchase_orders.index') }}"
           class="px-4 py-2 rounded-lg border border-gray-600 text-gray-300 hover:bg-gray-700 font-semibold">
          Limpiar
        </a>
      @endif
      <span class="ml-auto text-sm text-gray-400">
        Mostrando <span class="text-gray-200 font-semibold">{{ $orders->count() }}</span> de
        <span class="text-gray-200 font-semibold">{{ $orders->total() }}</span> registros
      </span>
    </div>
  </form>

  {{-- Tabla --}}
  <div class="bg-gray-900 border border-gray-700 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-800 text-gray-300 uppercase text-xs">
          <tr>
            <th class="text-left px-4 py-2">N° OC</th>
            <th class="text-left px-4 py-2">Proveedor</th>
            <th class="text-left px-4 py-2">Fecha</th>
            <th class="text-left px-4 py-2">Ítems</th>
            <th class="text-left px-4 py-2">Estado</th>
            <th class="text-right px-4 py-2">Total</th>
            <th class="text-right px-4 py-2">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700 text-gray-200">
          @forelse($orders as $o)
            @php
              $chip = [
                'borrador' => 'bg-gray-700 text-gray-100',
                'enviado'  => 'bg-blue-700 text-blue-100',
                'recibido' => 'bg-emerald-700 text-emerald-100',
                'cerrado'  => 'bg-purple-700 text-purple-100',
              ][$o->status] ?? 'bg-gray-700 text-gray-100';
            @endphp
            <tr class="hover:bg-gray-800/60">
              <td class="px-4 py-2 font-mono">{{ $o->order_number }}</td>
              <td class="px-4 py-2">{{ $o->supplier?->name ?? '—' }}</td>
              <td class="px-4 py-2">{{ \Illuminate\Support\Carbon::parse($o->order_date)->format('d/m/Y') }}</td>
              <td class="px-4 py-2">{{ (int)($o->items_count ?? 0) }}</td>
              <td class="px-4 py-2">
                <span class="text-xs px-2 py-1 rounded {{ $chip }}">{{ ucfirst($o->status) }}</span>
              </td>
              <td class="px-4 py-2 text-right">₲ {{ number_format($o->total, 0, ',', '.') }}</td>
              <td class="px-4 py-2 text-right">
                <div class="inline-flex gap-2">
                  <a href="{{ route('purchase_orders.show', $o) }}"
                     class="px-3 py-1 rounded border border-gray-600 text-gray-300 hover:bg-gray-700">
                    Ver
                  </a>
                  @if (Route::has('purchase_orders.edit'))
                    <a href="{{ route('purchase_orders.edit', $o) }}"
                       class="px-3 py-1 rounded border border-gray-600 text-gray-300 hover:bg-gray-700">
                      Editar
                    </a>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                No se encontraron órdenes con los filtros actuales.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Footer tabla: total de la página (no global) --}}
    <div class="flex items-center justify-between px-4 py-3 border-t border-gray-700 text-sm">
      @php
        $pageTotal = $orders->sum('total');
      @endphp
      <div class="text-gray-300">
        Total de esta página:
        <span class="text-green-300 font-bold">₲ {{ number_format($pageTotal, 0, ',', '.') }}</span>
      </div>
      <div>
        {{ $orders->onEachSide(1)->links() }}
      </div>
    </div>
  </div>

</div>
@endsection
