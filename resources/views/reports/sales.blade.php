@extends('layout.admin')

@section('content')
<div class="mb-6 flex items-center justify-between">
  <h1 class="text-2xl font-bold text-emerald-400">📊 Reporte de Ventas</h1>

  {{-- Botones de exportación --}}
  <div class="flex gap-2">
    <a href="{{ route('reports.sales.pdf', request()->all()) }}" target="_blank"
       class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-500 transition">
      📄 Exportar PDF
    </a>
    <a href="{{ route('reports.sales.print', request()->all()) }}" target="_blank"
       class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-500 transition">
      🖨️ Imprimir
    </a>
  </div>
</div>

{{-- 🔍 Filtro de fechas --}}
<form method="GET" class="flex gap-3 mb-6">
  <input type="date" name="from" value="{{ request('from') }}"
         class="rounded-lg bg-zinc-900 border border-zinc-700 text-zinc-100 px-3 py-2">
  <input type="date" name="to" value="{{ request('to') }}"
         class="rounded-lg bg-zinc-900 border border-zinc-700 text-zinc-100 px-3 py-2">

  <button type="submit"
          class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 transition">
    Filtrar
  </button>
</form>

{{-- Tabla --}}
<div class="rounded-xl border border-zinc-800 bg-zinc-900 overflow-hidden shadow">
  <table class="min-w-full text-sm">
    <thead class="bg-zinc-800 text-zinc-300 uppercase text-xs tracking-wider">
      <tr>
        <th class="px-4 py-3">#</th>
        <th class="px-4 py-3">Cliente</th>
        <th class="px-4 py-3">Modo</th>
        <th class="px-4 py-3">Estado</th>
        <th class="px-4 py-3">Total</th>
        <th class="px-4 py-3">Fecha</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-zinc-800 text-zinc-200">
      @forelse($sales as $s)
        <tr class="hover:bg-zinc-800/50 transition">
          <td class="px-4 py-3 font-mono">#{{ $s->id }}</td>
          <td class="px-4 py-3">{{ $s->client->name ?? '—' }}</td>
          <td class="px-4 py-3">{{ ucfirst($s->modo_pago ?? '—') }}</td>
          <td class="px-4 py-3">
            <x-status-badge 
              :color="$s->estado === 'aprobado' ? 'emerald' : ($s->estado === 'rechazado' ? 'red' : 'yellow')" 
              :label="ucfirst($s->estado)" />
          </td>
          <td class="px-4 py-3 font-semibold text-emerald-400">
            Gs. {{ number_format($s->total ?? 0, 0, ',', '.') }}
          </td>
          <td class="px-4 py-3 text-zinc-400">
            {{ optional($s->fecha)->format('Y-m-d') ?? $s->created_at->format('Y-m-d') }}
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="px-4 py-6 text-center text-zinc-500 italic">
            🚫 No se encontraron ventas en el rango seleccionado
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  {{-- Total general --}}
  <div class="p-4 text-right font-semibold text-emerald-400 border-t border-zinc-800">
    Total: Gs. {{ number_format($sales->sum('total'), 0, ',', '.') }}
  </div>
</div>
@endsection
