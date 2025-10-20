{{-- resources/views/dashboard/contact/index.blade.php --}}
@extends('layout.admin')

@section('title', 'Panel de Contactos')

@section('content')
<div class="space-y-6">

  {{-- Encabezado --}}
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-800">Panel de Contactos</h1>

    {{-- Filtros rápidos (si necesitas más, agrega al formulario de abajo) --}}
    <form method="GET" class="hidden md:flex items-center gap-2">
      <select name="channel"
              class="rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
        <option value="">Todos los canales</option>
        @foreach (['telegram'=>'Telegram','whatsapp'=>'WhatsApp','email'=>'Email','sms'=>'SMS','call'=>'Llamada'] as $val=>$lbl)
          <option value="{{ $val }}" @selected(request('channel')===$val)>{{ $lbl }}</option>
        @endforeach
      </select>

      <input type="date" name="from" value="{{ request('from') }}"
             class="rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
      <input type="date" name="to"   value="{{ request('to')   }}"
             class="rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">

      <button class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm">
        Filtrar
      </button>
      <a href="{{ url()->current() }}"
         class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md text-sm">Limpiar</a>
    </form>
  </div>

  {{-- KPIs --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white rounded-lg border p-4">
      <div class="text-gray-500 text-sm">Enviados hoy</div>
      <div class="text-3xl font-semibold">{{ $kpis['sent_today'] ?? 0 }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
      <div class="text-gray-500 text-sm">Fallidos hoy</div>
      <div class="text-3xl font-semibold text-red-600">{{ $kpis['fails_today'] ?? 0 }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
      <div class="text-gray-500 text-sm">En cola</div>
      <div class="text-3xl font-semibold text-amber-600">{{ $kpis['queued'] ?? 0 }}</div>
    </div>
    <div class="bg-white rounded-lg border p-4">
      <div class="text-gray-500 text-sm">Entregados</div>
      <div class="text-3xl font-semibold text-emerald-600">{{ $kpis['delivered'] ?? 0 }}</div>
    </div>
  </div>

  {{-- Lista de logs --}}
  <div class="bg-white rounded-lg border overflow-hidden">
    <div class="px-4 py-3 border-b flex items-center justify-between">
      <div class="font-semibold">Últimos contactos</div>

      {{-- Buscador simple por mensaje --}}
      <form method="GET" class="flex items-center gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar en mensaje…"
               class="w-64 rounded-md border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
        <button class="px-3 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-md text-sm">Buscar</button>
      </form>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-4 py-2 text-left">Fecha</th>
            <th class="px-4 py-2 text-left">Cliente/Contacto</th>
            <th class="px-4 py-2 text-left">Canal</th>
            <th class="px-4 py-2 text-left">Dirección</th>
            <th class="px-4 py-2 text-left">Estado</th>
            <th class="px-4 py-2 text-left">Mensaje</th>
            <th class="px-4 py-2 text-left">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($logs as $log)
            @php
              $contact = $log->contactable;
              $channelColors = [
                'telegram' => 'bg-sky-100 text-sky-800',
                'whatsapp' => 'bg-green-100 text-green-800',
                'email'    => 'bg-amber-100 text-amber-800',
                'sms'      => 'bg-purple-100 text-purple-800',
                'call'     => 'bg-gray-200 text-gray-800',
              ];
              $statusColors = [
                'queued'    => 'bg-amber-100 text-amber-800',
                'sent'      => 'bg-blue-100 text-blue-800',
                'delivered' => 'bg-emerald-100 text-emerald-800',
                'read'      => 'bg-emerald-100 text-emerald-800',
                'fail'      => 'bg-red-100 text-red-800',
              ];
            @endphp
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-2 whitespace-nowrap">
                {{ optional($log->sent_at ?? $log->created_at)->format('d/m/Y H:i') }}
              </td>
              <td class="px-4 py-2">
                @if($contact)
                  <a href="{{ route('clients.edit', $contact->id) }}" class="text-blue-600 hover:underline">
                    {{ $contact->name ?? ('#'.$contact->id) }}
                  </a>
                @else
                  <span class="text-gray-500">—</span>
                @endif
              </td>
              <td class="px-4 py-2">
                <span class="px-2 py-1 rounded {{ $channelColors[$log->channel] ?? 'bg-gray-200 text-gray-800' }}">
                  {{ ucfirst($log->channel) }}
                </span>
              </td>
              <td class="px-4 py-2 capitalize">{{ $log->direction }}</td>
              <td class="px-4 py-2">
                <span class="px-2 py-1 rounded {{ $statusColors[$log->status] ?? 'bg-gray-200 text-gray-800' }}">
                  {{ ucfirst($log->status) }}
                </span>
              </td>
              <td class="px-4 py-2 max-w-[28rem]">
                <div title="{{ $log->message }}" class="truncate">{{ $log->message }}</div>
              </td>
              <td class="px-4 py-2">
                @if($log->channel === 'whatsapp' && ($contact?->phone))
                  @php $wa = preg_replace('/\D+/', '', $contact->phone); @endphp
                  <a target="_blank" href="https://wa.me/{{ $wa }}?text={{ urlencode('Hola de Katuete Importados 👋') }}"
                     class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700 text-xs">WhatsApp</a>
                @endif

                @if($log->channel === 'telegram' && ($contact?->telegram_chat_id))
                  <a target="_blank" href="https://t.me/{{ $contact->telegram_chat_id }}"
                     class="px-3 py-1 rounded bg-sky-600 text-white hover:bg-sky-700 text-xs">Abrir chat</a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-8 text-center text-gray-500">Sin registros.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-4 py-3 border-t">
      {{ $logs->links() }}
    </div>
  </div>

  {{-- Sidebar auxiliar (opcional): últimos clientes para campañas manuales --}}
  @if(isset($clients) && $clients->count())
    <div class="bg-white rounded-lg border p-4">
      <div class="font-semibold mb-2">Clientes recientes</div>
      <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
        @foreach($clients as $c)
          <div class="border rounded p-3">
            <div class="font-medium truncate">{{ $c->name }}</div>
            <div class="text-xs text-gray-500 truncate">{{ $c->phone ?? 's/teléfono' }}</div>
            <div class="mt-2 flex items-center gap-2">
              @if($c->phone)
                @php $wa = preg_replace('/\D+/', '', $c->phone); @endphp
                <a target="_blank" href="https://wa.me/{{ $wa }}?text={{ urlencode('Hola '.($c->name ?? '').' 👋') }}"
                   class="px-2 py-1 rounded bg-green-600 text-white hover:bg-green-700 text-xs">WhatsApp</a>
              @endif
              <a href="{{ route('clients.edit', $c) }}"
                 class="px-2 py-1 rounded bg-gray-800 text-white hover:bg-gray-700 text-xs">Abrir</a>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

</div>
@endsection
