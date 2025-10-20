{{-- resources/views/clients/partials/references.blade.php --}}
<div class="bg-gray-900 text-white rounded-xl border-2 border-green-400 p-6 mt-6">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-semibold text-green-300">📞 Referencias de {{ $client->name }}</h2>
  </div>

  {{-- Mensajes de error (si los hubiera) --}}
  @if ($errors->any())
    <div class="bg-red-900/20 text-red-200 border border-red-400 rounded px-3 py-2 mb-3 text-sm">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  {{-- Formulario de alta (modo mixto) --}}
  <form method="POST" action="{{ route('clients.references.store', $client) }}" class="grid md:grid-cols-3 gap-3 mb-5">
    @csrf

    {{-- A) Elegir cliente existente (opcional) --}}
    <div class="md:col-span-3">
      <label class="block text-xs text-gray-400 mb-1">Referencia como cliente (opcional)</label>
      <select name="referenced_client_id"
              class="w-full rounded bg-gray-800 border border-gray-700 px-3 py-2 text-sm focus:border-green-400 focus:ring-0">
        <option value="">— Seleccionar cliente existente —</option>
        @foreach(\App\Models\Client::orderBy('name')->limit(200)->get() as $c)
          <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }} ({{ $c->phone ?? 's/tel' }})</option>
        @endforeach
      </select>
      <p class="text-xs text-gray-500 mt-1">Si seleccionás un cliente, dejá vacíos los campos de abajo.</p>
    </div>

    {{-- B) O ingresar como contacto libre --}}
    <div>
      <label class="block text-xs text-gray-400 mb-1">Nombre (si no es cliente)</label>
      <input type="text" name="name" placeholder="Nombre completo"
             class="w-full rounded bg-gray-800 border border-gray-700 px-3 py-2 text-sm focus:border-green-400 focus:ring-0">
    </div>

    <div>
      <label class="block text-xs text-gray-400 mb-1">Relación</label>
      <input type="text" name="relationship" placeholder="Amigo, vecino…"
             class="w-full rounded bg-gray-800 border border-gray-700 px-3 py-2 text-sm focus:border-green-400 focus:ring-0">
    </div>

    <div>
      <label class="block text-xs text-gray-400 mb-1">Teléfono</label>
      <input type="text" name="phone" placeholder="Teléfono"
             class="w-full rounded bg-gray-800 border border-gray-700 px-3 py-2 text-sm focus:border-green-400 focus:ring-0">
    </div>

    <div>
      <label class="block text-xs text-gray-400 mb-1">Email (opcional)</label>
      <input type="email" name="email" placeholder="Email"
             class="w-full rounded bg-gray-800 border border-gray-700 px-3 py-2 text-sm focus:border-green-400 focus:ring-0">
    </div>

    <div>
      <label class="block text-xs text-gray-400 mb-1">Telegram (usuario)</label>
      <input type="text" name="telegram" placeholder="@usuario"
             class="w-full rounded bg-gray-800 border border-gray-700 px-3 py-2 text-sm focus:border-green-400 focus:ring-0">
      <p class="text-xs text-gray-500 mt-1">Ej: @pepito — solo para contactos libres.</p>
    </div>

    <div>
      <label class="block text-xs text-gray-400 mb-1">Dirección (opcional)</label>
      <input type="text" name="address" placeholder="Dirección"
             class="w-full rounded bg-gray-800 border border-gray-700 px-3 py-2 text-sm focus:border-green-400 focus:ring-0">
    </div>

    <div>
      <label class="block text-xs text-gray-400 mb-1">Observación</label>
      <input type="text" name="note" placeholder="Observación"
             class="w-full rounded bg-gray-800 border border-gray-700 px-3 py-2 text-sm focus:border-green-400 focus:ring-0">
    </div>

    <div class="md:col-span-3 flex justify-end">
      <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded">
        Agregar referencia
      </button>
    </div>
  </form>

  {{-- Listado --}}
  <div class="border border-gray-700 rounded-lg">
    <div class="px-4 py-2 border-b border-gray-700 text-sm text-gray-300 flex items-center justify-between">
      <span>Referencias registradas</span>
      <span class="text-xs text-gray-400">{{ $client->references->count() }} contacto(s)</span>
    </div>

    <div class="divide-y divide-gray-800">
      @forelse($client->references as $r)
        @php
          $isClientRef = !is_null($r->referenced_client_id);
          $name  = $isClientRef ? ($r->referenced_client->name ?? '—')  : ($r->name ?? '—');
          $phone = $isClientRef ? ($r->referenced_client->phone ?? null) : ($r->phone ?? null);
          $email = $isClientRef ? ($r->referenced_client->email ?? null) : ($r->email ?? null);
          $telegram = $isClientRef ? null : ($r->telegram ?? null);

          $phoneLink = $phone ? preg_replace('/\s+/', '', $phone) : null;
          $waLink = $phone ? preg_replace('/\D+/', '', $phone) : null;
          $tgLink = $telegram ? 'https://t.me/'.ltrim($telegram, '@') : null;
        @endphp

        <div class="flex items-center justify-between px-4 py-2">
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <span class="text-green-300 font-semibold truncate">{{ $name }}</span>
              <span class="text-[10px] uppercase tracking-wide rounded px-1.5 py-0.5
                           {{ $isClientRef ? 'bg-sky-700' : 'bg-gray-700' }}">
                {{ $isClientRef ? 'Cliente' : 'Contacto' }}
              </span>
            </div>

            <div class="text-sm text-gray-400 truncate">
              {{ $r->relationship ?? 'sin relación' }}
              @if($phone) • 📞 {{ $phone }} @endif
              @if($email) • ✉️ {{ $email }} @endif
              @if($telegram) • 📨 Telegram: {{ $telegram }} @endif
            </div>

            @if($isClientRef && $r->referenced_client)
              <div class="text-xs text-gray-500">
                <a href="{{ route('clients.edit', $r->referenced_client) }}" class="hover:underline">
                  Abrir ficha del cliente
                </a>
              </div>
            @endif
          </div>

          <div class="flex items-center gap-2 shrink-0">
            @if($phoneLink)
              <a href="tel:{{ $phoneLink }}" class="px-2 py-1 text-xs rounded bg-gray-700 hover:bg-gray-600">Llamar</a>
            @endif
            @if($waLink)
              <a href="https://wa.me/{{ $waLink }}?text={{ urlencode('Hola '.($name ?: '').', te saludo de Katuete Importados 👋') }}"
                 target="_blank" class="px-2 py-1 text-xs rounded bg-green-700 hover:bg-green-600">WhatsApp</a>
            @endif
            @if($tgLink)
              <a href="{{ $tgLink }}" target="_blank"
                 class="px-2 py-1 text-xs rounded bg-sky-700 hover:bg-sky-600">Telegram</a>
            @endif

            <form method="POST" action="{{ route('clients.references.destroy', [$client, $r]) }}"
                  onsubmit="return confirm('¿Eliminar esta referencia?')">
              @csrf @method('DELETE')
              <button class="px-3 py-1.5 rounded bg-red-600 hover:bg-red-700 text-white text-xs">
                Eliminar
              </button>
            </form>
          </div>
        </div>
      @empty
        <div class="px-4 py-6 text-center text-gray-400 text-sm">Sin referencias.</div>
      @endforelse
    </div>
  </div>
</div>
