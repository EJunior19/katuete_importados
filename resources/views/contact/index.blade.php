@extends('layout.admin')

@section('content')
<div class="w-full px-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-3xl font-bold text-green-400">Panel de Contacto</h1>
    <a href="{{ route('dashboard.index') }}" class="px-3 py-1.5 rounded-lg border border-gray-700 bg-gray-800 text-gray-300 text-sm">← Volver</a>
  </div>

  {{-- KPIs --}}
  <div class="grid md:grid-cols-4 gap-4 mb-4">
    <x-kpi-card title="Enviados hoy" :value="$kpis['sent_today']" color="emerald"/>
    <x-kpi-card title="Fallidos hoy" :value="$kpis['fails_today']" color="red"/>
    <x-kpi-card title="En cola" :value="$kpis['queued']" color="amber"/>
    <x-kpi-card title="Sin canal" :value="$kpis['clients_no_channel']" color="zinc"/>
  </div>

  {{-- Barra de filtros --}}
  <form method="GET" class="grid md:grid-cols-4 gap-3 mb-4">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar cliente / RUC" class="rounded bg-gray-800 border border-gray-700 px-3 py-2">
    <select name="channel" class="rounded bg-gray-800 border border-gray-700 px-3 py-2">
      <option value="">Todos los canales</option>
      @foreach(['telegram','whatsapp','email','sms'] as $c)
        <option value="{{ $c }}" @selected(request('channel')===$c)>{{ ucfirst($c) }}</option>
      @endforeach
    </select>
    <select name="state" class="rounded bg-gray-800 border border-gray-700 px-3 py-2">
      <option value="">Todos los estados</option>
      @foreach(['queued'=>'En cola','sent'=>'Enviado','fail'=>'Falló'] as $k=>$v)
        <option value="{{ $k }}" @selected(request('state')===$k)>{{ $v }}</option>
      @endforeach
    </select>
    <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">Filtrar</button>
  </form>

  {{-- Acciones rápidas (enviar mensaje) --}}
  <div class="bg-gray-900 border-2 border-green-400 rounded-xl p-4 mb-6">
    <form method="POST" action="{{ route('contact.send', $clients->first()?->id ?? 1) }}"
      onsubmit="this.action=this.action.replace(/send\/\d+/, 'send/'+document.getElementById('client_id_sel').value)">
      @csrf
      <div class="grid md:grid-cols-4 gap-3 items-end">
        <div>
          <label class="block text-xs text-gray-400 mb-1">Cliente</label>
          <select id="client_id_sel" class="w-full rounded bg-gray-800 border border-gray-700 px-3 py-2">
            @foreach($clients as $c)
              <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1">Canal</label>
          <select name="channel" class="w-full rounded bg-gray-800 border border-gray-700 px-3 py-2">
            <option value="">Automático</option>
            <option value="telegram">Telegram</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="email">Email</option>
            <option value="sms">SMS</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs text-gray-400 mb-1">Mensaje</label>
          <input type="text" name="message" class="w-full rounded bg-gray-800 border border-gray-700 px-3 py-2"
            placeholder="Hola 👋 tenemos nuevas promos para vos…" required>
        </div>
      </div>
      <div class="flex justify-end mt-3">
        <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">Enviar</button>
      </div>
    </form>
  </div>

  {{-- Tabla de logs --}}
  <div class="bg-gray-900 border-2 border-green-400 rounded-xl">
    <div class="overflow-x-auto rounded-t-xl">
      <table class="min-w-full text-sm text-left">
        <thead class="bg-gray-800 text-gray-200 uppercase text-xs tracking-wide">
          <tr>
            <th class="px-4 py-3">Fecha</th>
            <th class="px-4 py-3">Cliente</th>
            <th class="px-4 py-3">Canal</th>
            <th class="px-4 py-3">Estado</th>
            <th class="px-4 py-3">Destino</th>
            <th class="px-4 py-3">Mensaje</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
          @foreach($logs as $l)
            <tr class="hover:bg-gray-800/60">
              <td class="px-4 py-3">{{ $l->created_at?->format('Y-m-d H:i') }}</td>
              <td class="px-4 py-3">
                <a class="text-green-300 hover:underline" href="{{ route('clients.edit',$l->client) }}">{{ $l->client?->name }}</a>
              </td>
              <td class="px-4 py-3 capitalize">{{ $l->channel }}</td>
              <td class="px-4 py-3">
                <x-status-badge :color="['queued'=>'amber','sent'=>'emerald','fail'=>'red'][$l->status] ?? 'zinc'"
                                :label="ucfirst($l->status)" />
              </td>
              <td class="px-4 py-3">
                @if($l->channel==='whatsapp' && ($l->meta['wa_link'] ?? false))
                  <a href="{{ $l->meta['wa_link'] }}" class="text-sky-300 hover:underline" target="_blank">Abrir WhatsApp</a>
                @else
                  <span class="text-gray-400">{{ $l->to_ref }}</span>
                @endif
              </td>
              <td class="px-4 py-3 text-gray-300 truncate max-w-[32rem]">{{ $l->message }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="p-4 border-t border-gray-700">
      {{ $logs->links() }}
    </div>
  </div>
</div>
@endsection
