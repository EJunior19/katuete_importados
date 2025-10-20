{{-- resources/views/clients/telegram.blade.php --}}
@extends('layout.admin')
@section('title','Telegram – '.$client->name)

@section('content')
<div class="p-4 text-gray-100"> {{-- ← NO usar .content-wrap acá --}}
  @if(session('ok'))
    <div class="mb-4 p-3 rounded bg-emerald-900/40 border border-emerald-600">{{ session('ok') }}</div>
  @endif
  @if(session('err'))
    <div class="mb-4 p-3 rounded bg-red-900/40 border border-red-600">{{ session('err') }}</div>
  @endif

  <div class="grid md:grid-cols-2 gap-6"> {{-- ← grilla correctamente cerrada --}}
    {{-- Columna izquierda: estado --}}
    <div class="bg-gray-900 rounded-2xl p-5 shadow">
      <h2 class="text-xl font-semibold mb-3">Estado de Telegram</h2>

      @if($client->is_telegram_linked ?? false)
        <p class="mb-2">✅ Vinculado desde: <strong>{{ optional($client->telegram_linked_at)->format('d/m/Y H:i') }}</strong></p>
        <p class="mb-4">chat_id:
          <code class="bg-black/40 px-2 py-1 rounded">{{ $client->telegram_chat_id }}</code>
        </p>

        <form method="POST" action="{{ route('clients.telegram.ping',$client) }}" class="inline">
          @csrf
          <button class="px-3 py-2 rounded bg-blue-700 hover:bg-blue-600">Probar envío</button>
        </form>

        <form method="POST" action="{{ route('clients.telegram.unlink',$client) }}" class="inline delete-form ml-2" data-name="la vinculación de Telegram">
          @csrf
          <button class="px-3 py-2 rounded bg-red-700 hover:bg-red-600">Desvincular</button>
        </form>
      @else
        <p class="mb-4">❌ Aún no vinculado</p>
        <form method="POST" action="{{ route('clients.telegram.generate',$client) }}" class="inline">
          @csrf
          <button class="px-3 py-2 rounded bg-emerald-700 hover:bg-emerald-600">Generar enlace mágico</button>
        </form>
      @endif
    </div>

    {{-- Columna derecha: vinculación desde el panel --}}
    <div class="bg-gray-900 rounded-2xl p-5 shadow">
      <h2 class="text-xl font-semibold mb-3">Vincular desde el Panel</h2>

      {{-- A) Enlace mágico --}}
      <div class="mb-6">
        <h3 class="font-semibold mb-2">A) Enlace mágico (recomendado)</h3>
        @if($client->telegram_link_token)
          @php
            $botUser = env('TELEGRAM_BOT_USERNAME', 'TuBotUsername');
            $deepLink = "https://t.me/{$botUser}?start={$client->telegram_link_token}";
          @endphp
          <p class="text-sm mb-2">Compartí este link. Al abrirlo en Telegram, queda vinculado automáticamente:</p>
          <div class="flex items-center gap-2">
            <input type="text" readonly class="w-full px-3 py-2 bg-black/40 rounded" value="{{ $deepLink }}">
            <button type="button" class="px-3 py-2 rounded bg-gray-800 hover:bg-gray-700"
              onclick="navigator.clipboard.writeText('{{ $deepLink }}')">Copiar</button>
          </div>
          <p class="text-xs mt-2 opacity-70">Sugerencia: enviá por WhatsApp.</p>
        @else
          <p class="text-sm mb-3">Generá un enlace único por cliente para vinculación 1-click.</p>
        @endif
      </div>

      {{-- B) Carga manual --}}
      <div>
        <h3 class="font-semibold mb-2">B) Carga manual (pegando chat_id)</h3>
        <form method="POST" action="{{ route('clients.telegram.save',$client) }}">
          @csrf
          <div class="flex gap-2">
            <input type="number" name="telegram_chat_id"
                   class="w-full px-3 py-2 bg-black/40 rounded"
                   placeholder="Ej: 7210653986"
                   value="{{ old('telegram_chat_id',$client->telegram_chat_id) }}">
            <button class="px-3 py-2 rounded bg-emerald-700 hover:bg-emerald-600">Guardar</button>
          </div>
          @error('telegram_chat_id')
            <div class="text-red-400 text-sm mt-2">{{ $message }}</div>
          @enderror
        </form>
      </div>
    </div>
  </div> {{-- /grid --}}
      {{-- 🔙 Botón de volver --}}
    <div class="mt-8 flex justify-end">
      <a href="{{ route('clients.show', $client) }}" 
         class="px-6 py-2 text-sm rounded-lg border border-gray-500 text-gray-300 hover:bg-gray-700 font-semibold shadow">
         ← Volver al cliente
      </a>
      <a href="{{ route('clients.index') }}" 
         class="ml-3 px-6 py-2 text-sm rounded-lg border border-green-500 text-green-400 hover:bg-green-600 hover:text-white font-semibold shadow">
         🧾 Volver a la lista
      </a>
    </div>

</div> {{-- /p-4 --}}
@endsection
