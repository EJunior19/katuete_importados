@extends('layout.admin')

@section('content')
<div class="w-full px-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold text-sky-400 flex items-center gap-2">
      🚛 Recepciones de compra
    </h1>

    <div class="flex gap-2">
      @if (Route::has('purchase_receipts.create'))
        <a href="{{ route('purchase_receipts.create') }}"
           class="px-4 py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-semibold shadow">
          Nueva recepción
        </a>
      @endif
    </div>
  </div>

  {{-- Filtros --}}
  <form method="GET" class="bg-gray-900 border border-gray-700 rounded-xl p-4 mb-4">
    <div class="grid md:grid-cols-4 gap-3">
      <div>
        <label class="text-xs text-gray-400">Buscar</label>
        <input type="text" name="q" value="{{ $q ?? '' }}"
               placeholder="N° recepción, N° OC o proveedor…"
               class="w-full rounded-lg bg-gray-800 border-gray-700 text-gray-100 px-3 py-2">
      </div>
      <div>
        <label class="text-xs text-gray-400">Estado</label>
        <select name="status" class="w-full rounded-lg bg-gray-800 border-gray-700 text-gray-100 px-3 py-2">
          <option value="">Todos</option>
          @foreach (['borrador','pendiente_aprobacion','aprobado','rechazado'] as $st)
            <option value="{{ $st }}" @selected(($status ?? '')===$st)>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-400">Por página</label>
        <select name="per_page" class="w-full rounded-lg bg-gray-800 border-gray-700 text-gray-100 px-3 py-2">
          @foreach ([10,15,25,50] as $pp)
            <option value="{{ $pp }}" @selected(($perPage ?? 15)===$pp)>{{ $pp }}</option>
          @endforeach
        </select>
      </div>
      <div class="flex items-end">
        <button class="w-full px-4 py-2 rounded-lg bg-gray-700 hover:bg-gray-600 text-white font-semibold">Filtrar</button>
      </div>
    </div>
  </form>

  {{-- Tabla --}}
  <div class="overflow-x-auto rounded-xl border border-gray-700">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-800 text-gray-300 uppercase text-xs">
        <tr>
          <th class="px-4 py-2 text-left">#</th>
          <th class="px-4 py-2 text-left">N° recepción</th>
          <th class="px-4 py-2 text-left">Orden de compra</th>
          <th class="px-4 py-2 text-left">Proveedor</th>
          <th class="px-4 py-2 text-left">Fecha recibida</th>
          <th class="px-4 py-2 text-left">Ítems</th>
          <th class="px-4 py-2 text-left">Estado</th>
          <th class="px-4 py-2 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-700">
        @forelse ($receipts as $r)
          <tr class="hover:bg-gray-800/60">
            <td class="px-4 py-2">{{ $r->id }}</td>
            <td class="px-4 py-2 font-mono">{{ $r->receipt_number }}</td>
            <td class="px-4 py-2">
              @if($r->order)
                <a href="{{ route('purchase_orders.show', $r->order) }}"
                   class="text-sky-300 hover:underline">
                  {{ $r->order->order_number }}
                </a>
              @else
                —
              @endif
            </td>
            <td class="px-4 py-2">
              {{ $r->order?->supplier?->name ?? '—' }}
            </td>
            <td class="px-4 py-2">
              {{ \Illuminate\Support\Carbon::parse($r->received_date)->format('d/m/Y') }}
            </td>
            <td class="px-4 py-2">{{ (int) $r->items_count }}</td>
            <td class="px-4 py-2">
              @php
                $chip = [
                  'borrador'              => 'bg-gray-700 text-gray-200',
                  'pendiente_aprobacion'  => 'bg-amber-700 text-amber-100',
                  'aprobado'              => 'bg-emerald-700 text-emerald-100',
                  'rechazado'             => 'bg-rose-700 text-rose-100',
                ][$r->status] ?? 'bg-gray-700 text-gray-200';
              @endphp
              <span class="px-2 py-1 text-xs rounded {{ $chip }}">
                {{ ucfirst(str_replace('_',' ',$r->status)) }}
              </span>
            </td>
            <td class="px-4 py-2 text-right">
              <div class="inline-flex gap-2">
                @if (Route::has('purchase_receipts.show'))
                  <a href="{{ route('purchase_receipts.show', $r) }}"
                     class="px-3 py-1 rounded border border-gray-600 text-gray-300 hover:bg-gray-700">
                    Ver
                  </a>
                @endif

                @if ($r->status === 'pendiente_aprobacion')
                  <form method="POST" action="{{ route('purchase_receipts.approve', $r) }}"
                        onsubmit="return confirm('¿Aprobar recepción y actualizar stock?')">
                    @csrf
                    <button class="px-3 py-1 rounded bg-emerald-700 hover:bg-emerald-800 text-white">
                      Aprobar
                    </button>
                  </form>

                  <form method="POST" action="{{ route('purchase_receipts.reject', $r) }}"
                        onsubmit="return confirm('¿Rechazar recepción?')">
                    @csrf
                    <button class="px-3 py-1 rounded bg-rose-700 hover:bg-rose-800 text-white">
                      Rechazar
                    </button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="px-4 py-6 text-center text-gray-400">
              No se encontraron recepciones.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Paginación --}}
  <div class="mt-4">
    {{ $receipts->links() }}
  </div>
</div>
@endsection
