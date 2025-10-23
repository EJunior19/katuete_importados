{{-- resources/views/sales/index.blade.php --}}
@extends('layout.admin')

@section('title','Ventas · CRM Katuete')

@push('styles')
  <style>
    .tbl-sticky thead { position: sticky; top: 0; z-index: 10; }
    .num { text-align: right; font-variant-numeric: tabular-nums; }
    .badge {
      display:inline-flex; align-items:center; gap:.375rem;
      font-size:.75rem; line-height:1; padding:.375rem .625rem; border-radius:.5rem;
      border-width:1px;
    }
    .badge-indigo  { color:#c7d2fe; border-color:#3730a3; background:#1e1b4b; }
    .badge-emerald { color:#bbf7d0; border-color:#065f46; background:#064e3b; }
    .badge-amber   { color:#fde68a; border-color:#92400e; background:#78350f; }
    .badge-red     { color:#fecaca; border-color:#7f1d1d; background:#7f1d1d33; }
    .badge-slate   { color:#cbd5e1; border-color:#475569; background:#0f172a; }
  </style>
@endpush

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-3xl font-bold text-emerald-400">📊 Ventas</h1>
  {{-- Botón nueva venta --}}
  <x-create-button route="{{ route('sales.create') }}" text="Nueva venta" />
</div>

{{-- Mensajes flash --}}
<x-flash-message />

{{-- 🔍 Filtros rápidos --}}
<form method="GET" class="mb-4 grid md:grid-cols-3 gap-3">
  <input type="text" name="q" value="{{ request('q') }}"
         placeholder="🔎 Buscar cliente, código o nota…"
         class="rounded-lg bg-zinc-900 border border-zinc-700 text-zinc-100 px-3 py-2 placeholder-zinc-500 focus:ring-2 focus:ring-emerald-600 focus:outline-none">

  <select name="estado"
          class="rounded-lg bg-zinc-900 border border-zinc-700 text-zinc-100 px-3 py-2 focus:ring-2 focus:ring-emerald-600">
    <option value="">— Estado —</option>
    @foreach(['pendiente'=>'Pendiente','aprobado'=>'Aprobado','rechazado'=>'Rechazado','anulado'=>'Anulado'] as $k=>$v)
      <option value="{{ $k }}" @selected(request('estado')===$k)>{{ $v }}</option>
    @endforeach
  </select>

  <div class="flex gap-2">
    <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 transition">Filtrar</button>
    <a href="{{ route('sales.index') }}"
       class="px-4 py-2 rounded-lg border border-zinc-600 text-zinc-300 hover:bg-zinc-800 transition">Limpiar</a>
  </div>
</form>

@php
  // Mapas de badges (colores consistentes)
  $badgeModo = [
    'contado'  => ['label'=>'Contado',  'class'=>'badge-emerald'],
    'credito'  => ['label'=>'Crédito',  'class'=>'badge-indigo'],
    'tarjeta'  => ['label'=>'Tarjeta',  'class'=>'badge-amber'],
  ];
  $badgeEstado = [
    'pendiente'=> ['label'=>'Pendiente', 'class'=>'badge-amber'],
    'aprobado' => ['label'=>'Aprobado',  'class'=>'badge-emerald'],
    'rechazado'=> ['label'=>'Rechazado', 'class'=>'badge-red'],
    'anulado'  => ['label'=>'Anulado',   'class'=>'badge-slate'],
  ];
@endphp

{{-- Tabla de ventas --}}
<div class="rounded-2xl border border-zinc-800 bg-zinc-900 shadow-lg overflow-hidden">
  <div class="overflow-x-auto tbl-sticky">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-zinc-800 text-zinc-300 uppercase text-xs tracking-wider">
          <th class="px-4 py-3 text-left w-16">#</th>
          <th class="px-4 py-3 text-left">Cliente</th>
          <th class="px-4 py-3 text-left">Modo</th>
          <th class="px-4 py-3 num">Gravadas</th>
          <th class="px-4 py-3 num">IVA</th>
          <th class="px-4 py-3 num">Total</th>
          <th class="px-4 py-3 text-left">Estado</th>
          <th class="px-4 py-3 text-left">Fecha</th>
          <th class="px-4 py-3 text-right">Acciones</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-zinc-800 text-zinc-200">
        @forelse($sales as $s)
          @php
            $gravadas = (int)($s->gravada_10 ?? 0) + (int)($s->gravada_5 ?? 0) + (int)($s->exento ?? 0);
            $modo = strtolower($s->modo_pago ?? '');
            $estado = strtolower($s->estado ?? '');
            $modoCfg = $badgeModo[$modo] ?? ['label'=>ucfirst($s->modo_pago ?? '—'), 'class'=>'badge-slate'];
            $estadoCfg = $badgeEstado[$estado] ?? ['label'=>ucfirst($s->estado ?? '—'), 'class'=>'badge-slate'];
          @endphp
          <tr class="hover:bg-zinc-800/50 transition">
            <td class="px-4 py-3 font-mono text-zinc-400">#{{ $s->id }}</td>

            <td class="px-4 py-3">
              {{ $s->client->name ?? '—' }}
              @if(isset($s->client->code))
                <span class="text-zinc-500 text-xs">· {{ $s->client->code }}</span>
              @endif
            </td>

            <td class="px-4 py-3">
              <span class="badge {{ $modoCfg['class'] }}">{{ $modoCfg['label'] }}</span>
            </td>

            <td class="px-4 py-3 num">Gs. {{ number_format($gravadas, 0, ',', '.') }}</td>
            <td class="px-4 py-3 num">Gs. {{ number_format($s->total_iva ?? 0, 0, ',', '.') }}</td>
            <td class="px-4 py-3 num font-semibold text-emerald-400">Gs. {{ number_format($s->total ?? 0, 0, ',', '.') }}</td>

            <td class="px-4 py-3">
              <x-status-badge
                :label="($s->status ?? 'Pendiente')" />
            </td>


            <td class="px-4 py-3 text-zinc-400">
              {{ optional($s->fecha)->format('Y-m-d') ?? $s->created_at->format('Y-m-d') }}
            </td>

            <td class="px-4 py-3 text-right">
              <div class="inline-flex gap-2">
                <a href="{{ route('sales.show',$s) }}"
                   class="px-3 py-1.5 rounded-lg border border-sky-600/40 text-sky-300 hover:bg-sky-900/30 transition">👁️ Ver</a>
                <a href="{{ route('sales.edit',$s) }}"
                   class="px-3 py-1.5 rounded-lg border border-amber-600/40 text-amber-300 hover:bg-amber-900/30 transition">✏️ Editar</a>

                @if(($s->estado ?? '') === 'aprobado')
                  <a href="{{ route('sales.print',$s) }}" target="_blank"
                     class="px-3 py-1.5 rounded-lg border border-indigo-600/40 text-indigo-300 hover:bg-indigo-900/30 transition">🖨️ Ticket</a>
                @endif

                {{-- Botón eliminar con SweetAlert --}}
                <x-delete-button :action="route('sales.destroy',$s)" :name="'la venta #'.$s->id" />
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="px-4 py-8 text-center text-zinc-400 italic">🚫 No hay ventas registradas</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Paginación --}}
  <div class="p-4 border-t border-zinc-800 flex items-center justify-between gap-3">
    <div class="text-xs text-zinc-400">
      Mostrando
      <span class="font-semibold text-zinc-200">{{ $sales->firstItem() ?? 0 }}</span>
      a
      <span class="font-semibold text-zinc-200">{{ $sales->lastItem() ?? 0 }}</span>
      de
      <span class="font-semibold text-zinc-200">{{ $sales->total() }}</span>
      ventas
    </div>
    <div>
      {{ $sales->withQueryString()->onEachSide(1)->links() }}
    </div>
  </div>
</div>
@endsection
