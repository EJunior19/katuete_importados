<div class="bg-gray-900 rounded-2xl p-5 shadow mt-8">
  <h2 class="text-xl font-semibold mb-3">Telegram</h2>

  @if(session('ok'))
    <div class="mb-4 p-3 rounded bg-emerald-900/40 border border-emerald-600">{{ session('ok') }}</div>
  @endif
  @if(session('err'))
    <div class="mb-4 p-3 rounded bg-red-900/40 border border-red-600">{{ session('err') }}</div>
  @endif

  @if($client->is_telegram_linked ?? false)
    <p class="mb-2">✅ Vinculado desde: <strong>{{ optional($client->telegram_linked_at)->format('d/m/Y H:i') }}</strong></p>
    <p class="mb-4">chat_id: <code class="bg-black/40 px-2 py-1 rounded">{{ $client->telegram_chat_id }}</code></p>

    <form method="POST" action="{{ route('clients.telegram.ping', $client) }}" class="inline">
      @csrf
      <button class="px-3 py-2 rounded bg-blue-700 hover:bg-blue-600">Probar envío</button>
    </form>

    <form method="POST" action="{{ route('clients.telegram.unlink', $client) }}" class="inline delete-form ml-2" data-name="la vinculación de Telegram">
      @csrf
      <button class="px-3 py-2 rounded bg-red-700 hover:bg-red-600">Desvincular</button>
    </form>
  @else
    <p class="mb-4">❌ Aún no vinculado</p>
    <form method="POST" action="{{ route('clients.telegram.generate', $client) }}" class="inline">
      @csrf
      <button class="px-3 py-2 rounded bg-emerald-700 hover:bg-emerald-600">Generar enlace mágico</button>
    </form>

    @if($client->telegram_link_token)
      @php
        $botUser = env('TELEGRAM_BOT_USERNAME', 'TuBotUsername');
        $deepLink = "https://t.me/{$botUser}?start={$client->telegram_link_token}";
      @endphp
      <div class="mt-4 flex items-center gap-2">
        <input type="text" readonly class="w-full px-3 py-2 bg-black/40 rounded" value="{{ $deepLink }}">
        <button type="button" class="px-3 py-2 rounded bg-gray-800 hover:bg-gray-700"
                onclick="navigator.clipboard.writeText('{{ $deepLink }}')">Copiar</button>
      </div>
      <p class="text-xs mt-2 opacity-70">Enviá este link al cliente por WhatsApp. Al tocarlo, queda vinculado.</p>
    @endif

    <div class="mt-6">
      <h3 class="font-semibold mb-2">Carga manual (opcional)</h3>
      <form method="POST" action="{{ route('clients.telegram.save', $client) }}">
        @csrf
        <div class="flex gap-2">
          <input type="number" name="telegram_chat_id" class="w-full px-3 py-2 bg-black/40 rounded" placeholder="Ej: 7210653986" value="{{ old('telegram_chat_id',$client->telegram_chat_id) }}">
          <button class="px-3 py-2 rounded bg-emerald-700 hover:bg-emerald-600">Guardar</button>
        </div>
        @error('telegram_chat_id')
          <div class="text-red-400 text-sm mt-2">{{ $message }}</div>
        @enderror
      </form>
    </div>
  @endif
</div>
