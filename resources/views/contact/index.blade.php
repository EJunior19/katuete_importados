{{-- resources/views/contact/panel.blade.php --}}
@extends('layout.admin')

@section('title','Panel de Contacto · CRM Katuete')

@push('styles')
  <style>
    /* ====== Dark mode SOLO para esta vista (scope .contact-dark) ====== */
    html.contact-dark body { background-color: #0b0f14 !important; color: #e5e7eb !important; }
    html.contact-dark header,
    html.contact-dark footer { background-color: #0b0f14 !important; border-color: #111827 !important; color:#cbd5e1 !important; }
    html.contact-dark header a, html.contact-dark footer a { color:#cbd5e1 !important; }
    html.contact-dark .topbar-input,
    html.contact-dark .topbar-input:focus { background:#0f172a !important; border-color:#1f2937 !important; color:#e5e7eb !important; }
    html.contact-dark #sidebar aside { background:#0b0f14 !important; border-color:#111827 !important; }

    /* Tabla / tarjetas */
    .card-dark { background:#0b0f14; border:1px solid rgba(16,185,129,0.3); border-radius: 0.75rem; }
    .card-plain { background:#0b0f14; border:1px solid #111827; border-radius: 0.75rem; }
    .thead-dark { background:#000; color:#d1d5db; position: sticky; top: 0; z-index: 10; }
    .tr-hover:hover { background: rgba(17,24,39,0.6); }
    .input-dark { background:#0f172a; border:1px solid #1f2937; color:#e5e7eb; border-radius:0.5rem; }
    .input-dark::placeholder { color:#6b7280; }
    .btn-emerald { background:#059669; color:white; }
    .btn-emerald:hover { background:#047857; }
    .link-wa { color:#7dd3fc; }
    .link-emerald { color:#34d399; }
    .text-muted { color:#94a3b8; }
    .border-muted { border-color:#1f2937; }

    /* Paginación Tailwind default override (cuando uses Laravel pagination) */
    html.contact-dark .pagination .page-link,
    html.contact-dark nav[role="navigation"] > div > span > span,
    html.contact-dark nav[role="navigation"] a { background:#0f172a; border-color:#1f2937; color:#e5e7eb; }
    html.contact-dark nav[role="navigation"] span[aria-current="page"] > span { background:#059669; border-color:#059669; color:#fff; }
  </style>
@endpush

@push('scripts')
  <script>
    // Agrega la clase SOLO en esta vista y la remueve al salir
    document.documentElement.classList.add('contact-dark');
    window.addEventListener('beforeunload', () => {
      document.documentElement.classList.remove('contact-dark');
    });
  </script>
@endpush

@section('content')
<div class="w-full px-4 md:px-6">
  {{-- Header --}}
  <div class="flex items-center justify-between mb-5">
    <h1 class="text-3xl font-bold" style="color:#34d399">Panel de Contacto</h1>
    <a href="{{ route('dashboard.index') }}"
       class="px-3 py-1.5 rounded-lg border border-muted bg-gray-900 text-gray-200 text-sm hover:bg-gray-800">
      ← Volver
    </a>
  </div>

  {{-- KPIs --}}
  <div class="grid md:grid-cols-4 gap-4 mb-6">
    <x-kpi-card title="Enviados hoy" :value="$kpis['sent_today']" color="emerald"/>
    <x-kpi-card title="Fallidos hoy" :value="$kpis['fails_today']" color="red"/>
    <x-kpi-card title="En cola" :value="$kpis['queued']" color="amber"/>
    <x-kpi-card title="Sin canal" :value="$kpis['clients_no_channel']" color="zinc"/>
  </div>

  {{-- Filtros --}}
  <form method="GET" class="grid md:grid-cols-4 gap-3 mb-6">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar cliente / RUC"
           class="input-dark px-3 py-2">

    <select name="channel" class="input-dark px-3 py-2">
      <option value="">Todos los canales</option>
      @foreach(['telegram','whatsapp','email','sms'] as $c)
        <option value="{{ $c }}" @selected(request('channel')===$c)>{{ ucfirst($c) }}</option>
      @endforeach
    </select>

    <select name="state" class="input-dark px-3 py-2">
      <option value="">Todos los estados</option>
      @foreach(['queued'=>'En cola','sent'=>'Enviado','fail'=>'Falló'] as $k=>$v)
        <option value="{{ $k }}" @selected(request('state')===$k)>{{ $v }}</option>
      @endforeach
    </select>

    <button class="btn-emerald px-4 py-2 rounded shadow">Filtrar</button>
  </form>

  {{-- Acciones rápidas --}}
  <div class="card-dark p-4 mb-6 shadow-lg">
    <form method="POST" action="{{ route('contact.send', $clients->first()?->id ?? 1) }}"
          onsubmit="this.action = this.action.replace(/send\/\d+/, 'send/' + document.getElementById('client_id_sel').value)">
      @csrf
      <div class="grid md:grid-cols-4 gap-3 items-end">
        <div>
          <label class="block text-xs text-muted mb-1">Cliente</label>
          <select id="client_id_sel" class="input-dark w-full px-3 py-2">
            @foreach($clients as $c)
              <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs text-muted mb-1">Canal</label>
          <select name="channel" class="input-dark w-full px-3 py-2">
            <option value="">Automático</option>
            <option value="telegram">Telegram</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="email">Email</option>
            <option value="sms">SMS</option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs text-muted mb-1">Mensaje</label>
          <input type="text" name="message" class="input-dark w-full px-3 py-2"
                 placeholder="Hola 👋 tenemos nuevas promos para vos…" required>
        </div>
      </div>

      <div class="flex justify-end mt-3">
        <button class="btn-emerald px-4 py-2 rounded shadow">Enviar</button>
      </div>
    </form>
  </div>

  {{-- Tabla de logs --}}
  <div class="card-plain shadow-lg">
    <div class="overflow-x-auto rounded-t-xl">
      <table class="min-w-full text-sm text-left">
        <thead class="thead-dark uppercase text-xs tracking-wide">
          <tr>
            <th class="px-4 py-3">Fecha</th>
            <th class="px-4 py-3">Cliente</th>
            <th class="px-4 py-3">Canal</th>
            <th class="px-4 py-3">Estado</th>
            <th class="px-4 py-3">Destino</th>
            <th class="px-4 py-3">Mensaje</th>
          </tr>
        </thead>

        <tbody class="divide-y border-muted">
          @forelse($logs as $l)
            <tr class="tr-hover">
              <td class="px-4 py-3 whitespace-nowrap">{{ $l->created_at?->format('Y-m-d H:i') }}</td>
              <td class="px-4 py-3">
                <a class="hover:underline link-emerald" href="{{ route('clients.edit',$l->client) }}">
                  {{ $l->client?->name }}
                </a>
              </td>
              <td class="px-4 py-3 capitalize">{{ $l->channel }}</td>
              <td class="px-4 py-3">
                <x-status-badge :color="['queued'=>'amber','sent'=>'emerald','fail'=>'red'][$l->status] ?? 'zinc'"
                                :label="ucfirst($l->status)" />
              </td>
              <td class="px-4 py-3">
                @if($l->channel==='whatsapp' && data_get($l->meta,'wa_link'))
                  <a href="{{ data_get($l->meta,'wa_link') }}" class="link-wa hover:underline" target="_blank">
                    Abrir WhatsApp
                  </a>
                @else
                  <span class="text-muted">{{ $l->to_ref }}</span>
                @endif
              </td>
              <td class="px-4 py-3 text-slate-300 truncate max-w-[36rem]" title="{{ $l->message }}">
                {{ $l->message }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-4 py-5 text-center text-muted">Sin registros para los filtros actuales.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Paginación --}}
    <div class="p-4 border-t border-muted flex items-center justify-between gap-3">
      <div class="text-xs text-muted">
        Mostrando
        <span class="font-semibold text-slate-300">{{ $logs->firstItem() ?? 0 }}</span>
        a
        <span class="font-semibold text-slate-300">{{ $logs->lastItem() ?? 0 }}</span>
        de
        <span class="font-semibold text-slate-300">{{ $logs->total() }}</span>
        registros
      </div>
      <div>
        {{ $logs->withQueryString()->onEachSide(1)->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
