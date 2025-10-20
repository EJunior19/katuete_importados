{{-- resources/views/admin/bot/index.blade.php --}}
@extends('layout.admin')

@section('title','Bot de Telegram – Panel')
@section('content')
<div class="p-6 text-gray-100 space-y-6">

  @if(session('ok'))
    <div class="p-3 rounded bg-emerald-900/40 border border-emerald-600">{{ session('ok') }}</div>
  @endif
  @if(session('err'))
    <div class="p-3 rounded bg-red-900/40 border border-red-600">{{ session('err') }}</div>
  @endif

  {{-- KPIs --}}
  <div class="grid md:grid-cols-4 gap-4">
    <div class="bg-gray-900 p-4 rounded-xl">
      <div class="text-sm opacity-70">Webhook</div>
      <div class="text-xl font-bold mt-1">
        @php $online = !empty($wh['url']); @endphp
        {!! $online ? '🟢 Online' : '🔴 Offline' !!}
      </div>
      <div class="text-xs mt-2 break-all opacity-70">{{ $wh['url'] ?? 'sin URL' }}</div>
    </div>
    <div class="bg-gray-900 p-4 rounded-xl">
      <div class="text-sm opacity-70">Clientes vinculados</div>
      <div class="text-2xl font-bold mt-1">{{ $linkedCount }}</div>
    </div>
    <div class="bg-gray-900 p-4 rounded-xl">
      <div class="text-sm opacity-70">Mensajes 24h</div>
      <div class="text-2xl font-bold mt-1">{{ $last24h }}</div>
    </div>
    <div class="bg-gray-900 p-4 rounded-xl">
      <div class="text-sm opacity-70">Errores 24h</div>
      <div class="text-2xl font-bold mt-1 text-red-400">{{ $last24hErrors }}</div>
    </div>
  </div>

  {{-- Acciones --}}
  <div class="bg-gray-900 p-4 rounded-xl space-y-3">
    <h2 class="text-lg font-semibold">Acciones</h2>
    <form method="POST" action="{{ route('bot.webhook.set') }}" class="flex gap-2">
      @csrf
      <input name="url" class="w-full px-3 py-2 bg-black/40 rounded" placeholder="https://.../api/telegram/webhook" value="{{ old('url', env('TELEGRAM_WEBHOOK_URL')) }}">
      <button class="px-4 py-2 rounded bg-blue-700 hover:bg-blue-600">Registrar Webhook</button>
    </form>

    <div class="flex gap-2">
      <form method="POST" action="{{ route('bot.webhook.test') }}">@csrf
        <button class="px-4 py-2 rounded bg-indigo-700 hover:bg-indigo-600">Probar webhook local</button>
      </form>
      <form method="POST" action="{{ route('bot.broadcast.test') }}">@csrf
        <button class="px-4 py-2 rounded bg-emerald-700 hover:bg-emerald-600">Broadcast de prueba</button>
      </form>
    </div>
  </div>

  {{-- Clientes vinculados --}}
  <div class="bg-gray-900 p-4 rounded-xl">
    <h2 class="text-lg font-semibold mb-3">Clientes vinculados (últimos)</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-gray-400">
          <tr>
            <th class="text-left p-2">ID</th>
            <th class="text-left p-2">Nombre</th>
            <th class="text-left p-2">Chat</th>
            <th class="text-left p-2">Vinculado</th>
            <th class="text-left p-2">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @foreach($clients as $c)
            <tr class="border-t border-gray-800">
              <td class="p-2">{{ $c->id }}</td>
              <td class="p-2">{{ $c->name }}</td>
              <td class="p-2"><code>{{ $c->telegram_chat_id }}</code></td>
              <td class="p-2">{{ optional($c->telegram_linked_at)->format('d/m/Y H:i') }}</td>
              <td class="p-2">
                <form method="POST" action="{{ route('bot.client.ping',$c) }}" class="inline">@csrf
                  <button class="px-3 py-1 rounded bg-blue-700 hover:bg-blue-600 text-xs">Ping</button>
                </form>
                <form method="POST" action="{{ route('bot.client.regen',$c) }}" class="inline ml-1">@csrf
                  <button class="px-3 py-1 rounded bg-gray-700 hover:bg-gray-600 text-xs">Regenerar link</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Últimos logs --}}
  <div class="bg-gray-900 p-4 rounded-xl">
    <h2 class="text-lg font-semibold mb-3">Últimos eventos</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-gray-400">
          <tr><th class="text-left p-2">Fecha</th><th class="text-left p-2">Cliente</th><th class="text-left p-2">Dir</th><th class="text-left p-2">Tipo</th><th class="text-left p-2">Estado</th><th class="text-left p-2">Mensaje</th></tr>
        </thead>
        <tbody>
          @foreach($logs as $l)
            <tr class="border-t border-gray-800">
              <td class="p-2">{{ $l->created_at->format('d/m H:i') }}</td>
              <td class="p-2">{{ $l->client?->name ?? '—' }}</td>
              <td class="p-2">{{ $l->direction }}</td>
              <td class="p-2">{{ $l->type ?? '—' }}</td>
              <td class="p-2 {{ $l->status==='ok'?'text-emerald-400':'text-red-400' }}">{{ $l->status }}</td>
              <td class="p-2 truncate max-w-[28rem]">{{ Str::limit($l->message,120) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
