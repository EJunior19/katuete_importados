@extends('layout.admin') {{-- usa tu layout base --}}

@section('content')
<style>
  body { font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji'; }

  /* ====== Título animado ====== */
  .title-animated{
    font-size: 1.6rem; font-weight: 800;
    background: linear-gradient(90deg,#00ff88, #60a5fa, #a78bfa, #00ff88);
    background-size: 300% 300%;
    -webkit-background-clip: text; background-clip: text; color: transparent;
    animation: flow 6s ease-in-out infinite;
    letter-spacing: .3px;
    text-shadow: 0 0 18px rgba(0,255,136,.12);
  }
  @keyframes flow { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }

  .section-sub { color:#9ca3af; font-size:.9rem }

  .card { background:#111827; color:#e5e7eb; border:1px solid #1f2937; border-radius:14px; padding:18px; box-shadow:0 0 10px rgba(0,255,136,0.05); }
  .kpi-card { text-align:center; padding:18px; transition:.2s; }
  .kpi-card:hover { background:#0f172a; transform:translateY(-2px); }
  .kpi-value { font-size:2rem; font-weight:700; color:#00ff88; }
  .kpi-label { font-size:.9rem; color:#9ca3af; }

  .filter-input { background:#0f172a; border:1px solid #1f2937; color:#e5e7eb; border-radius:8px; padding:10px 12px; width:100%; }
  .btn { background:#00ff88; color:#0b1322; font-weight:700; border:none; border-radius:10px; padding:10px 14px; transition:.2s; box-shadow: 0 0 0 rgba(0,0,0,0); }
  .btn:hover { background:#00e67f; transform: translateY(-1px); box-shadow:0 6px 18px rgba(0,255,136,.18); }
  .btn-outline { border:1px solid #00ff88; color:#00ff88; background:transparent; border-radius:10px; padding:8px 12px; transition:.2s; font-weight:600; }
  .btn-outline:hover { background:#00ff8833; }

  .btn-ghost { color:#e5e7eb; border:1px solid #374151; background:transparent; border-radius:10px; padding:8px 12px; transition:.2s; }
  .btn-ghost:hover { background:#111827; border-color:#4b5563; }

  .tag { padding:4px 10px; border-radius:999px; font-size:.8rem; font-weight:700; }
  .tag-red { background:#7f1d1d; color:#fecaca; }
  .tag-yellow { background:#78350f; color:#fde68a; }
  .tag-green { background:#064e3b; color:#a7f3d0; }
  .tag-gray { background:#1f2937; color:#9ca3af; }

  table { width:100%; border-collapse:collapse; }
  th, td { padding:10px 8px; border-bottom:1px solid #1f2937; }
  th { text-align:left; color:#9ca3af; font-weight:700; font-size:.9rem; }
  td { font-size:.92rem; }
  tbody tr { transition: background .15s ease; }
  tbody tr:hover { background: #0f172a; }

  .table-container { overflow:auto; max-height:70vh; border-radius: 10px; }

  /* Barra de filtros sticky */
  .filter-sticky { position: sticky; top: 0; z-index: 30; background: #0b1220; padding-top: 6px; padding-bottom: 10px; }
</style>

{{-- ====== Encabezado con botón Volver + acciones ====== --}}
<div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between mb-3">
  <div class="flex items-center gap-3">
    <button class="btn-ghost" onclick="window.history.length > 1 ? history.back() : window.location.assign('{{ route('dashboard') }}')">
      ⬅ Volver
    </button>
    <div>
      <h1 class="title-animated">📊 Panel de Créditos Automáticos</h1>
      <div class="section-sub">Monitorea vencidos, notificaciones y estados en tiempo real</div>
    </div>
  </div>

  <div class="flex items-center gap-2">
    <a href="{{ route('credits.stats') }}" class="btn">Ver estadísticas</a>
    @if (\Illuminate\Support\Facades\Route::has('credits.logs'))
      <a href="{{ route('credits.logs') }}" class="btn-outline">Ver logs</a>
    @endif
  </div>
</div>

{{-- Badge de estado del webhook --}}
<div class="text-sm mb-3">
  Webhook:
  {!! $webhookOk
      ? '<span class="tag tag-green">Online</span>'
      : '<span class="tag tag-red">Offline</span>' !!}
</div>

{{-- Alertas de acciones --}}
@if(session('ok'))   <div class="card" style="border-color:#00ff88">{{ session('ok') }}</div>@endif
@if(session('err'))  <div class="card" style="border-color:#ef4444">{{ session('err') }}</div>@endif

{{-- ====== KPIs ====== --}}
<div class="grid md:grid-cols-4 gap-4 mb-6">
  <div class="card kpi-card">
    <div class="kpi-value">{{ $hoyVencidos }}</div>
    <div class="kpi-label">Créditos vencidos (hoy)</div>
  </div>
  <div class="card kpi-card">
    <div class="kpi-value">{{ $prox3d }}</div>
    <div class="kpi-label">Vencen en 3 días</div>
  </div>
  <div class="card kpi-card">
    <div class="kpi-value">{{ $notificadosHoy }}</div>
    <div class="kpi-label">Notificados hoy</div>
  </div>
  <div class="card kpi-card">
    <div class="kpi-value">{{ $fallasHoy }}</div>
    <div class="kpi-label">Errores o fallos</div>
  </div>
</div>

{{-- ====== Filtros (sticky) ====== --}}
<div class="card mb-5 filter-sticky">
  <form class="grid md:grid-cols-6 gap-3 items-end">
    <input class="filter-input" name="s" placeholder="🔍 Buscar cliente o RUC" value="{{ request('s') }}">
    <select class="filter-input" name="estado" title="Estado de crédito">
      <option value="">Todos los estados</option>
      @foreach(['pending'=>'Pendiente','partial'=>'Parcial','vencido'=>'Vencido','paid'=>'Pagado'] as $k=>$v)
        <option value="{{ $k }}" @selected(request('estado')===$k)>{{ $v }}</option>
      @endforeach
    </select>
    <input class="filter-input" type="date" name="desde" value="{{ request('desde') }}" title="Desde">
    <input class="filter-input" type="date" name="hasta" value="{{ request('hasta') }}" title="Hasta">
    <button class="btn" title="Aplicar filtros">Filtrar</button>

    {{-- (opcional) export CSV si lo activás en rutas/controlador
    <a class="btn-outline" href="{{ route('credits.export', request()->query()) }}">⬇️ Exportar CSV</a>
    --}}
  </form>

  {{-- Leyenda de estados --}}
  <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-400">
    <span class="tag tag-red">Vencido</span>
    <span class="tag tag-yellow">Parcial/Pendiente</span>
    <span class="tag tag-green">Pagado</span>
    <span class="tag tag-gray">Sin vincular a Telegram</span>
  </div>
</div>

{{-- ====== Tabla de créditos ====== --}}
<div class="card">
  <div class="text-sm text-gray-400 mb-3">
    Último evento: {{ $ultimoEvento ? \Carbon\Carbon::parse($ultimoEvento)->diffForHumans() : '—' }}
  </div>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Cliente</th>
          <th>RUC</th>
          <th>Estado</th>
          <th>Vence</th>
          <th>Saldo</th>
          <th>Últ. notif.</th>
          <th>Telegram</th>
          <th style="width:220px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($q as $c)
          @php
            $map = ['vencido'=>'tag-red','partial'=>'tag-yellow','pending'=>'tag-yellow','paid'=>'tag-green'];
          @endphp
          <tr>
            <td>{{ $c->client->name ?? '—' }}</td>
            <td>{{ $c->client->ruc ?? '—' }}</td>
            <td><span class="tag {{ $map[$c->status] ?? 'tag-gray' }}">{{ ucfirst($c->status) }}</span></td>
            <td>{{ $c->due_date?->format('d/m/Y') }}</td>
            <td>Gs. {{ number_format($c->balance,0,',','.') }}</td>
            <td>{{ $c->last_at ? \Carbon\Carbon::parse($c->last_at)->diffForHumans() : '—' }}</td>
            <td>
              @if($c->client?->telegram_chat_id)
                <span class="tag tag-green">Vinculado</span>
              @else
                <span class="tag tag-gray">No</span>
              @endif
            </td>
            <td>
              <div class="flex flex-wrap gap-2">
                @if($c->client?->telegram_chat_id)
                  <form method="POST" action="{{ route('credits.remind', $c->id) }}">
                    @csrf
                    <button class="btn-outline" onclick="return confirm('¿Enviar recordatorio por Telegram?')">📩 Recordar</button>
                  </form>
                @endif
                <a class="btn-outline"
                   href="https://wa.me/595984784509?text={{ urlencode('Cliente '.$c->client->name.' — cuota '.$c->due_date?->format('d/m').' Gs. '.number_format($c->balance,0,',','.')) }}"
                   target="_blank">💬 WhatsApp</a>
                <a class="btn-ghost" href="{{ route('credits.index') }}">🔎 Ver créditos</a>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center text-gray-500 py-4">No hay registros</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3">{{ $q->links() }}</div>
</div>
@endsection
