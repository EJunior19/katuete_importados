{{-- resources/views/sales/index.blade.php --}}
@extends('layout.admin')

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
    @foreach(['pendiente'=>'Pendiente','aprobado'=>'Aprobado','rechazado'=>'Rechazado'] as $k=>$v)
      <option value="{{ $k }}" @selected(request('estado')===$k)>{{ $v }}</option>
    @endforeach
  </select>

  <div class="flex gap-2">
    <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 transition">Filtrar</button>
    <a href="{{ route('sales.index') }}" 
       class="px-4 py-2 rounded-lg border border-zinc-600 text-zinc-300 hover:bg-zinc-800 transition">Limpiar</a>
  </div>
</form>

{{-- Tabla de ventas --}}
<div class="rounded-2xl border border-zinc-800 bg-zinc-900 shadow-lg overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-zinc-800 text-zinc-300 uppercase text-xs tracking-wider">
          <th class="px-4 py-3">#</th>
          <th class="px-4 py-3">Cliente</th>
          <th class="px-4 py-3">Modo</th>
          <th class="px-4 py-3">Gravadas</th>
          <th class="px-4 py-3">IVA</th>
          <th class="px-4 py-3">Total</th>
          <th class="px-4 py-3">Estado</th>
          <th class="px-4 py-3">Fecha</th>
          <th class="px-4 py-3 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-zinc-800 text-zinc-200">
        @forelse($sales as $s)
          @php
            $gravadas = ($s->gravada_10 ?? 0) + ($s->gravada_5 ?? 0) + ($s->exento ?? 0);
          @endphp
          <tr class="hover:bg-zinc-800/50 transition">
            <td class="px-4 py-3 font-mono text-zinc-400">#{{ $s->id }}</td>
            <td class="px-4 py-3">{{ $s->client->name ?? '—' }}</td>
            <td class="px-4 py-3">
              <x-status-badge color="indigo" :label="ucfirst($s->modo_pago ?? '—')" />
            </td>
            <td class="px-4 py-3">Gs. {{ number_format($gravadas, 0, ',', '.') }}</td>
            <td class="px-4 py-3">Gs. {{ number_format($s->total_iva ?? 0, 0, ',', '.') }}</td>
            <td class="px-4 py-3 font-semibold text-emerald-400">Gs. {{ number_format($s->total ?? 0, 0, ',', '.') }}</td>
            <td class="px-4 py-3">
              <x-status-badge color="indigo" :label="ucfirst($s->modo_pago ?? '—')" />
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
                @if($s->estado === 'aprobado')
                  <a href="{{ route('sales.print',$s) }}" target="_blank"
                     class="px-3 py-1.5 rounded-lg border border-indigo-600/40 text-indigo-300 hover:bg-indigo-900/30 transition">🖨️ Ticket</a>
                @endif

                {{-- Botón eliminar con SweetAlert --}}
                <x-delete-button 
                  :action="route('sales.destroy',$s)" 
                  :name="'la venta #'.$s->id" />
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

  <div class="p-4 border-t border-zinc-800">
    {{ $sales->withQueryString()->links() }}
  </div>
</div>
@endsection
