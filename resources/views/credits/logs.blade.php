@extends('layout.admin')

@section('content')
<style>
  .card{background:#111827;color:#e5e7eb;border:1px solid #1f2937;border-radius:14px;padding:18px}
  .tag{padding:3px 10px;border-radius:999px;font-size:.8rem;font-weight:700}
  .tag-green{background:#064e3b;color:#a7f3d0}
  .tag-red{background:#7f1d1d;color:#fecaca}
  .tag-yellow{background:#78350f;color:#fde68a}
  .tag-gray{background:#1f2937;color:#9ca3af}
  table{width:100%;border-collapse:collapse}
  th,td{padding:10px 8px;border-bottom:1px solid #1f2937}
  th{color:#9ca3af;text-align:left}
</style>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-emerald-400">🧾 Auditoría de Notificaciones</h1>
  <a href="{{ route('credits.dashboard') }}" class="border border-emerald-400 text-emerald-400 rounded px-3 py-2">⬅ Volver</a>
</div>

<div class="card mb-4">
  <form class="grid md:grid-cols-6 gap-3 items-end">
    <input class="card" name="s" placeholder="🔍 Cliente / RUC / #Crédito" value="{{ request('s') }}">
    <select class="card" name="type">
      <option value="">Tipo</option>
      @foreach(['notified'=>'Enviado','error'=>'Error'] as $k=>$v)
        <option value="{{ $k }}" @selected(request('type')===$k)>{{ $v }}</option>
      @endforeach
    </select>
    <input class="card" type="date" name="desde" value="{{ request('desde') }}">
    <input class="card" type="date" name="hasta" value="{{ request('hasta') }}">
    <button class="bg-emerald-400 text-black rounded px-3 py-2 font-semibold">Filtrar</button>
    <a class="border border-emerald-400 text-emerald-400 rounded px-3 py-2 text-center" href="{{ route('credits.logs') }}">Limpiar</a>
  </form>
</div>

<div class="card">
  <div class="overflow-auto">
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Tipo</th>
          <th>Crédito</th>
          <th>Cliente</th>
          <th>RUC</th>
          <th>Estado crédito</th>
          <th>Meta</th>
        </tr>
      </thead>
      <tbody>
        @forelse($q as $row)
          @php
            $meta = $row->meta ? json_decode($row->meta, true) : [];
            $tipoClass = $row->type === 'notified' ? 'tag-green' : 'tag-red';
            $estadoClass = [
              'vencido'=>'tag-red',
              'pending'=>'tag-yellow',
              'partial'=>'tag-yellow',
              'pendiente'=>'tag-yellow',
              'paid'=>'tag-green',
              'pagado'=>'tag-green',
            ][$row->credit_status] ?? 'tag-gray';
          @endphp
          <tr>
            <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</td>
            <td><span class="tag {{ $tipoClass }}">{{ strtoupper($row->type) }}</span></td>
            <td>#{{ $row->credit_id }}</td>
            <td>{{ $row->client_name ?? '—' }}</td>
            <td>{{ $row->client_ruc ?? '—' }}</td>
            <td><span class="tag {{ $estadoClass }}">{{ ucfirst($row->credit_status) }}</span></td>
            <td>
              @if(isset($meta['auto']))
                <span class="tag tag-gray">auto: {{ $meta['auto'] }}</span>
              @endif
              @if(isset($meta['manual']) && $meta['manual'])
                <span class="tag tag-gray">manual</span>
              @endif
              @if(isset($meta['retry']) && $meta['retry'])
                <span class="tag tag-yellow">retry</span>
              @endif>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-gray-400 py-4">Sin registros</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3">
    {{ $q->links() }}
  </div>
</div>
@endsection
