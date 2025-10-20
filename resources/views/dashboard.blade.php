@extends('layout.admin')
@section('content')

<h1 class="text-2xl font-bold text-gray-100 mb-4">📊 Panel principal</h1>

{{-- Tarjetas resumen --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

  {{-- Clientes --}}
  <div class="bg-gradient-to-br from-blue-600 to-blue-500 text-white rounded-xl shadow p-4">
    <div class="font-medium">Clientes registrados</div>
    <div class="text-3xl font-bold">{{ $clientes }}</div>
    <div class="text-sm mt-3 border-t border-white/20 pt-2 flex items-center justify-between">
      <a href="{{ route('clients.index') }}" class="hover:underline">Ver clientes</a>
      <i class="fas fa-users"></i>
    </div>
  </div>

  {{-- Clientes vinculados a Telegram --}}
  <div class="bg-gradient-to-br from-emerald-600 to-emerald-500 text-white rounded-xl shadow p-4">
    <div class="font-medium">Clientes vinculados (Telegram)</div>
    <div class="text-3xl font-bold">{{ $clientesVinculados }}</div>
    <div class="text-sm mt-3 border-t border-white/20 pt-2 flex items-center justify-between">
      <a href="{{ route('bot.index') }}" class="hover:underline">Abrir panel del bot</a>
      <span class="text-xs px-2 py-0.5 rounded bg-black/30">{{ $webhookOnline ? 'Webhook Online' : 'Webhook Offline' }}</span>
    </div>
  </div>

  {{-- Créditos: vencen en 3 días --}}
  <div class="bg-gradient-to-br from-amber-600 to-amber-500 text-white rounded-xl shadow p-4">
    <div class="font-medium">Vencen en 3 días</div>
    <div class="text-3xl font-bold">{{ $vencen3dias }}</div>
    <div class="text-sm mt-3 border-t border-white/20 pt-2 flex items-center justify-between">
      <a href="{{ route('credits.dashboard') }}" class="hover:underline">Panel de créditos</a>
      <i class="fa-solid fa-hourglass-half"></i>
    </div>
  </div>

  {{-- Notificaciones 24h --}}
  <div class="bg-gradient-to-br from-purple-600 to-purple-500 text-white rounded-xl shadow p-4">
    <div class="font-medium">Notificaciones 24h</div>
    <div class="text-3xl font-bold">{{ $msg24h }}</div>
    <div class="text-sm mt-3 border-t border-white/20 pt-2 flex items-center justify-between">
      <span class="{{ $err24h ? 'text-red-200' : 'text-green-100' }}">
        {{ $err24h ? ($err24h.' errores') : 'sin errores' }}
      </span>
      <i class="fa-solid fa-bell"></i>
    </div>
  </div>
</div>

{{-- Segunda fila de KPIs compactos --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <div class="bg-gray-900 rounded-xl border border-gray-700 p-4">
    <div class="text-sm text-gray-400">Créditos vencidos (hoy)</div>
    <div class="text-2xl text-white font-semibold">{{ $vencidosHoy }}</div>
  </div>
  <div class="bg-gray-900 rounded-xl border border-gray-700 p-4">
    <div class="text-sm text-gray-400">Créditos vencidos (total)</div>
    <div class="text-2xl text-white font-semibold">{{ $vencidosTotales }}</div>
  </div>
  <div class="bg-gray-900 rounded-xl border border-gray-700 p-4">
    <div class="text-sm text-gray-400">Webhook</div>
    <div class="text-2xl font-semibold {{ $webhookOnline ? 'text-emerald-400' : 'text-red-400' }}">
      {{ $webhookOnline ? 'Online' : 'Offline' }}
    </div>
  </div>
</div>

{{-- Accesos rápidos --}}
<div class="bg-gray-900 rounded-xl shadow border border-gray-700 mb-6">
  <div class="px-4 py-2 border-b border-gray-700 font-semibold text-gray-200">
    ⚡ Accesos rápidos
  </div>
  <div class="p-4 flex flex-wrap gap-2">
    <x-create-button route="{{ route('clients.create') }}" text="Nuevo cliente" />
    <a href="{{ route('clients.index') }}" 
       class="px-3 py-1.5 text-sm border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition">📋 Lista de clientes</a>
    <a href="{{ route('credits.dashboard') }}" 
       class="px-3 py-1.5 text-sm border border-emerald-600 text-emerald-300 rounded-lg hover:bg-emerald-700/30 transition">🧮 Panel de créditos</a>
    <a href="{{ route('bot.index') }}" 
       class="px-3 py-1.5 text-sm border border-indigo-600 text-indigo-300 rounded-lg hover:bg-indigo-700/30 transition">🤖 Bot de Telegram</a>
  </div>
</div>

{{-- Actividad reciente (dos columnas) --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
  {{-- Últimos eventos de Telegram --}}
  <div class="bg-gray-900 rounded-xl shadow border border-gray-700">
    <div class="px-4 py-2 border-b border-gray-700 font-semibold text-gray-200 flex items-center justify-between">
      <span>🗒️ Últimos eventos (Telegram)</span>
      <a href="{{ route('bot.index') }}" class="text-xs text-indigo-300 hover:underline">ver panel</a>
    </div>
    <div class="p-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-gray-400">
          <tr>
            <th class="text-left p-2">Fecha</th>
            <th class="text-left p-2">Cliente</th>
            <th class="text-left p-2">Dir</th>
            <th class="text-left p-2">Tipo</th>
            <th class="text-left p-2">Estado</th>
            <th class="text-left p-2">Mensaje</th>
          </tr>
        </thead>
        <tbody>
          @forelse($ultimosTG as $l)
          <tr class="border-t border-gray-800">
            <td class="p-2 text-gray-300">{{ $l->created_at->format('d/m H:i') }}</td>
            <td class="p-2 text-gray-200">{{ $l->client?->name ?? '—' }}</td>
            <td class="p-2 text-gray-300">{{ $l->direction }}</td>
            <td class="p-2 text-gray-300">{{ $l->type ?? '—' }}</td>
            <td class="p-2 {{ $l->status==='ok'?'text-emerald-400':'text-red-400' }}">{{ $l->status }}</td>
            <td class="p-2 text-gray-300 truncate max-w-[22rem]">{{ \Illuminate\Support\Str::limit($l->message, 80) }}</td>
          </tr>
          @empty
          <tr><td class="p-3 text-gray-400" colspan="6">Sin eventos recientes.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Últimos cambios en créditos --}}
  <div class="bg-gray-900 rounded-xl shadow border border-gray-700">
    <div class="px-4 py-2 border-b border-gray-700 font-semibold text-gray-200">💳 Últimos cambios de créditos</div>
    <div class="p-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-gray-400">
          <tr>
            <th class="text-left p-2">Fecha</th>
            <th class="text-left p-2">Cliente</th>
            <th class="text-left p-2">Estado</th>
            <th class="text-left p-2">Vence</th>
            <th class="text-left p-2">Saldo</th>
          </tr>
        </thead>
        <tbody>
          @forelse($ultimosCreditos as $c)
          <tr class="border-t border-gray-800">
            <td class="p-2 text-gray-300">{{ optional($c->updated_at)->format('d/m H:i') }}</td>
            <td class="p-2 text-gray-200">{{ $c->client?->name }}</td>
            <td class="p-2">
              <span class="px-2 py-0.5 rounded text-xs
                @if(in_array($c->status,['vencido','overdue'])) bg-red-700 text-white
                @elseif(in_array($c->status,['pendiente','pending','partial'])) bg-amber-700 text-white
                @else bg-gray-700 text-gray-200 @endif">
                {{ ucfirst($c->status) }}
              </span>
            </td>
            <td class="p-2 text-gray-300">{{ optional($c->due_date)->format('d/m/Y') }}</td>
            <td class="p-2 text-gray-300">Gs. {{ number_format((int)$c->balance,0,',','.') }}</td>
          </tr>
          @empty
          <tr><td class="p-3 text-gray-400" colspan="5">Sin cambios recientes.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection
