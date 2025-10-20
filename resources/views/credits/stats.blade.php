@extends('layout.admin')

@section('content')
<style>
  .card{background:#111827;color:#e5e7eb;border:1px solid #1f2937;border-radius:14px;padding:18px}
  .kpi{font-size:2rem;font-weight:800;color:#00ff88}
  .btn{background:#00ff88;color:#0b1322;font-weight:700;border-radius:8px;padding:10px 14px}
  .btn-outline{border:1px solid #00ff88;color:#00ff88;border-radius:8px;padding:8px 12px}
</style>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-emerald-400">📊 Estadísticas de Créditos</h1>
  <a href="{{ route('credits.dashboard') }}" class="btn-outline">⬅ Volver al Panel</a>
</div>

<div class="grid md:grid-cols-5 gap-4">
  <div class="card"><div>Total créditos</div><div class="kpi">{{ $total }}</div></div>
  <div class="card"><div>Vencidos</div><div class="kpi">{{ $vencidos }}</div></div>
  <div class="card"><div>Pagados</div><div class="kpi">{{ $pagados }}</div></div>
  <div class="card"><div>Pendientes/Parcial</div><div class="kpi">{{ $pendientes }}</div></div>
  <div class="card"><div>Monto en mora</div><div class="kpi">Gs. {{ number_format((int)($moraGs ?? 0),0,',','.') }}</div></div>
</div>

<div class="grid md:grid-cols-2 gap-4 mt-6">
  {{-- Gráfico semanal de vencimientos --}}
  <div class="card">
    <div class="mb-2 font-semibold">Vencimientos por semana (últimas 8)</div>
    <canvas id="chartWeekly" height="120"></canvas>
  </div>

  {{-- Top 5 morosos --}}
  <div class="card">
    <div class="mb-2 font-semibold">Top 5 morosos</div>
    <table class="w-full" style="border-collapse:collapse">
      <thead>
        <tr style="color:#9ca3af">
          <th class="py-2 text-left">Cliente</th>
          <th class="py-2 text-left">RUC</th>
          <th class="py-2 text-right">Saldo</th>
        </tr>
      </thead>
      <tbody>
      @forelse($top as $row)
        <tr>
          <td class="py-2">{{ $row->client->name ?? '—' }}</td>
          <td class="py-2">{{ $row->client->ruc ?? '—' }}</td>
          <td class="py-2 text-right">Gs. {{ number_format((int)$row->total, 0, ',', '.') }}</td>
        </tr>
      @empty
        <tr><td colspan="3" class="py-4 text-center text-gray-400">Sin datos</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const wkLabels = @json($wkLabels ?? []);
  const wkData   = @json($wkData ?? []);
  const ctx = document.getElementById('chartWeekly').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: { labels: wkLabels, datasets: [{ label: 'Vencimientos', data: wkData }] },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      plugins: { legend: { display: false } }
    }
  });
</script>
@endpush
