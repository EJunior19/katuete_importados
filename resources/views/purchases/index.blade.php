{{-- resources/views/purchases/index.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="w-full px-6">
  {{-- Header --}}
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <h1 class="text-3xl font-bold text-green-400 flex items-center gap-2">
      🧾 Compras
      @isset($purchases)
        <span class="text-sm font-medium text-gray-400">({{ number_format($purchases->total()) }} registros)</span>
      @endisset
    </h1>

    <div class="flex items-center gap-2">
      <x-create-button route="{{ route('purchases.create') }}" text="Nueva compra" />
    </div>
  </div>

  {{-- Flash --}}
  <x-flash-message />

  {{-- Filtros / Búsqueda --}}
  <div class="bg-gray-900 text-white rounded-xl shadow-2xl border-2 border-green-400 mb-4">
    <form method="GET" action="{{ route('purchases.index') }}" class="p-4">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
        {{-- Búsqueda libre --}}
        <div class="md:col-span-4">
          <label for="q" class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Buscar</label>
          <input
            id="q" name="q" type="text"
            value="{{ request('q') }}"
            placeholder="Factura, código o proveedor…"
            class="w-full rounded-lg bg-gray-800 border border-gray-700 focus:border-green-400 focus:ring-0 px-3 py-2 text-sm placeholder-gray-500"
          />
        </div>

        {{-- Proveedor (si viene el listado) --}}
        @isset($suppliers)
          <div class="md:col-span-3">
            <label for="supplier_id" class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Proveedor</label>
            <select id="supplier_id" name="supplier_id"
                    class="w-full rounded-lg bg-gray-800 border border-gray-700 focus:border-green-400 focus:ring-0 px-3 py-2 text-sm">
              <option value="">Todos</option>
              @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ (string)request('supplier_id') === (string)$s->id ? 'selected' : '' }}>
                  {{ $s->name }}
                </option>
              @endforeach
            </select>
          </div>
        @endisset

        {{-- Estado --}}
        <div class="md:col-span-2">
          <label for="status" class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Estado</label>
          <select id="status" name="status"
                  class="w-full rounded-lg bg-gray-800 border border-gray-700 focus:border-green-400 focus:ring-0 px-3 py-2 text-sm">
            <option value="" {{ request('status') === null || request('status') === '' ? 'selected' : '' }}>Todos</option>
            <option value="pendiente" {{ request('status') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="aprobado"  {{ request('status') === 'aprobado'  ? 'selected' : '' }}>Aprobado</option>
            <option value="rechazado" {{ request('status') === 'rechazado' ? 'selected' : '' }}>Rechazado</option>
          </select>
        </div>

        {{-- Fecha desde / hasta --}}
        <div class="md:col-span-1">
          <label for="from" class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Desde</label>
          <input type="date" id="from" name="from" value="{{ request('from') }}"
                 class="w-full rounded-lg bg-gray-800 border border-gray-700 focus:border-green-400 focus:ring-0 px-3 py-2 text-sm" />
        </div>
        <div class="md:col-span-1">
          <label for="to" class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Hasta</label>
          <input type="date" id="to" name="to" value="{{ request('to') }}"
                 class="w-full rounded-lg bg-gray-800 border border-gray-700 focus:border-green-400 focus:ring-0 px-3 py-2 text-sm" />
        </div>

        {{-- Botones --}}
        <div class="md:col-span-1 flex gap-2">
          <button type="submit"
                  class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-green-500/20 border border-green-400 text-green-300 hover:bg-green-500/30 text-sm font-medium transition">
            Buscar
          </button>
          <a href="{{ route('purchases.index') }}"
             class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-gray-300 hover:bg-gray-700 text-sm font-medium transition">
            Limpiar
          </a>
        </div>
      </div>
    </form>
  </div>

  {{-- Tabla --}}
  <div class="bg-gray-900 text-white rounded-xl shadow-2xl border-2 border-green-400">
    <div class="relative">
      {{-- contenedor con scroll y header sticky --}}
      <div class="overflow-x-auto max-h-[70vh] rounded-t-xl">
        <table class="min-w-full text-sm text-left">
          <thead class="bg-gray-800 text-gray-200 uppercase text-xs tracking-wide sticky top-0 z-10">
            <tr>
              <th class="px-6 py-3 text-right w-16">ID</th>
              <th class="px-6 py-3">Factura</th>
              <th class="px-6 py-3">Proveedor</th>
              <th class="px-6 py-3">Fecha</th>
              <th class="px-6 py-3">Estado</th>
              <th class="px-6 py-3 text-right">Total</th>
              <th class="px-6 py-3 text-right w-40">Acciones</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-700">
            @forelse($purchases as $p)
              <tr class="hover:bg-gray-800/60 transition">
                <td class="px-6 py-3 text-right tabular-nums">{{ $p->id }}</td>

                <td class="px-6 py-3">
                  <div class="font-mono text-sm text-gray-200 whitespace-nowrap">
                    {{ $p->invoice_number ?: ($p->code ?? '—') }}
                  </div>
                </td>

                <td class="px-6 py-3">
                  {{ $p->supplier?->name ?? '—' }}
                </td>

                <td class="px-6 py-3 whitespace-nowrap">
                  {{ optional($p->purchased_at)->format('Y-m-d') ?? '—' }}
                </td>

                <td class="px-6 py-3">
                  @php
                    $st = strtolower($p->status ?? '');
                    $badge = match($st){
                      'aprobado'  => ['emerald', 'Aprobado'],
                      'pendiente' => ['amber',   'Pendiente'],
                      'rechazado' => ['red',     'Rechazado'],
                      default     => ['zinc',    ucfirst($p->status ?? '—')],
                    };
                  @endphp
                  <x-status-badge :color="$badge[0]" :label="$badge[1]" />
                </td>

                <td class="px-6 py-3 text-right font-semibold tabular-nums">
                  @money($p->total_amount ?? 0)
                </td>

                <td class="px-6 py-3">
                  <div class="flex justify-end">
                    <x-action-buttons
                      :show="route('purchases.show', $p)"
                      :edit="route('purchases.edit', $p)"
                      :delete="route('purchases.destroy', $p)"
                      :name="'la compra '.($p->invoice_number ?: ($p->code ?? '#'.$p->id))"
                    />
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-6 py-12">
                  <div class="flex flex-col items-center justify-center text-center text-gray-400">
                    <div class="text-5xl mb-3">🗂️</div>
                    <p class="font-semibold">Sin compras</p>
                    <p class="text-sm">Creá tu primera compra para comenzar.</p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Paginación --}}
      <div class="p-4 border-t border-gray-700">
        {{ $purchases->appends(request()->query())->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
