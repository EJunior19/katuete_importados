@extends('layout.admin')

@section('content')
{{-- ========= Encabezado ========= --}}
<div class="mb-5">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold text-black-100 flex items-center gap-2">
      💳 Créditos
      <span class="text-xs font-normal text-black-400 align-middle hidden md:inline">
        Gestión de cuentas por cobrar
      </span>
    </h1>
  </div>
</div>

<x-flash-message />

{{-- ========= Barra de filtros compacta ========= --}}
<form method="GET" class="bg-slate-900/90 border border-slate-700/80 rounded-xl p-3 md:p-4 mb-4"
      x-data="{ q: '{{ request('q') }}' }">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5">

    {{-- Buscar --}}
    <div class="lg:col-span-4">
      <label class="block text-[11px] uppercase tracking-wide text-slate-400 mb-1">Buscar</label>
      <div class="relative">
        <input type="text" name="q" x-model="q"
               @input.debounce.400ms="$root.submit()"
               placeholder="Cliente, #crédito o #venta"
               class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 pl-9 py-2 text-sm text-slate-200
                      focus:outline-none focus:ring-2 focus:ring-emerald-500">
        <span class="absolute left-3 top-2.5 text-slate-500">🔎</span>
      </div>
    </div>

    {{-- Estado --}}
    <div class="lg:col-span-2">
      <label class="block text-[11px] uppercase tracking-wide text-slate-400 mb-1">Estado</label>
      <select name="status"
              class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-slate-200"
              onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach(['pendiente'=>'Pendiente','pagado'=>'Pagado','vencido'=>'Vencido'] as $k=>$v)
          <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
        @endforeach
      </select>
    </div>

    {{-- Vence desde / hasta --}}
    <div class="lg:col-span-2">
      <label class="block text-[11px] uppercase tracking-wide text-slate-400 mb-1">Vence desde</label>
      <input type="date" name="due_from" value="{{ request('due_from') }}"
             class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-slate-200"
             onchange="this.form.submit()">
    </div>
    <div class="lg:col-span-2">
      <label class="block text-[11px] uppercase tracking-wide text-slate-400 mb-1">Vence hasta</label>
      <input type="date" name="due_to" value="{{ request('due_to') }}"
             class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-slate-200"
             onchange="this.form.submit()">
    </div>

    {{-- Orden --}}
    <div class="lg:col-span-2">
      <label class="block text-[11px] uppercase tracking-wide text-slate-400 mb-1">Orden</label>
      <select name="order"
              class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-slate-200"
              onchange="this.form.submit()">
        <option value="due_asc"  @selected(request('order','due_asc')==='due_asc')>Vencimiento ↑</option>
        <option value="due_desc" @selected(request('order')==='due_desc')>Vencimiento ↓</option>
        <option value="bal_desc" @selected(request('order')==='bal_desc')>Saldo ↓</option>
      </select>
    </div>

    {{-- Segunda línea: toggle semana + por página + limpiar --}}
    <div class="lg:col-span-12 flex flex-wrap items-center justify-between gap-2 pt-1">
      <label class="inline-flex items-center gap-2 text-sm text-slate-300">
        <input type="checkbox" name="this_week" value="1"
               @checked(request()->boolean('this_week'))
               onchange="this.form.submit()">
        Solo próximos 7 días
      </label>

      <div class="flex items-center gap-2">
        <select name="per_page"
                class="rounded-lg bg-slate-950 border border-slate-700 px-2 py-2 text-sm text-slate-200"
                onchange="this.form.submit()">
          @foreach([10,15,25,50,100] as $n)
            <option value="{{ $n }}" @selected((int)request('per_page',15)===$n)>{{ $n }}/pág</option>
          @endforeach
        </select>
        <a href="{{ route('credits.index') }}"
           class="px-3 py-2 rounded-lg border border-slate-600 text-slate-200 bg-slate-800 hover:bg-slate-700 transition">
          Limpiar
        </a>
      </div>
    </div>
  </div>
</form>

{{-- ========= Tabla ========= --}}
<div class="bg-slate-900 rounded-xl shadow-md border border-slate-700">
  <div class="overflow-x-auto rounded-t-xl">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-800/95 text-slate-200 uppercase text-[11px] tracking-wider sticky top-0 z-10">
        <tr>
          <th class="px-4 py-3 text-left">#</th>
          <th class="px-4 py-3 text-left">Cliente</th>
          <th class="px-4 py-3 text-left">Venta</th>
          <th class="px-4 py-3 text-right">Monto</th>
          <th class="px-4 py-3 text-right">Saldo</th>
          <th class="px-4 py-3 text-left">Vencimiento</th>
          <th class="px-4 py-3 text-left">Vence en</th>
          <th class="px-4 py-3 text-center">Estado</th>
          <th class="px-4 py-3 text-right w-[260px]">Acciones</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-slate-800 text-slate-100">
        @forelse($credits as $credit)
          @php
            $days = $credit->days_to_due ?? ( $credit->due_date ? now()->startOfDay()->diffInDays($credit->due_date, false) : null );
            $rowClass =
              $credit->status==='vencido' ? 'ring-1 ring-rose-900/40 bg-rose-950/20' :
              ($days !== null && $days >= 0 && $days <= 7 ? 'bg-amber-900/10' : '');
            $lastPayment = $credit->payments->first(); // with() en el controlador
          @endphp

          <tr class="hover:bg-slate-800/50 transition {{ $rowClass }}">
            <td class="px-4 py-3 font-mono text-slate-300">#{{ $credit->id }}</td>
            <td class="px-4 py-3">
              <div class="flex flex-col">
                <span class="font-medium">{{ $credit->client->name ?? '—' }}</span>
                <span class="text-xs text-slate-400">CI/RUC: {{ $credit->client->ruc ?? '—' }}
              </div>
            </td>
            <td class="px-4 py-3">#{{ $credit->sale->id ?? '—' }}</td>

            <td class="px-4 py-3 text-right">
              <span class="tabular-nums">Gs. {{ number_format($credit->amount,0,',','.') }}</span>
            </td>

            <td class="px-4 py-3 text-right">
              <span class="tabular-nums font-semibold {{ (int)$credit->balance === 0 ? 'text-emerald-400' : 'text-slate-200' }}">
                Gs. {{ number_format($credit->balance,0,',','.') }}
              </span>
            </td>

            <td class="px-4 py-3">{{ $credit->due_date?->format('Y-m-d') ?? '—' }}</td>

            <td class="px-4 py-3">
              @if(is_null($days))
                <span class="text-slate-400">—</span>
              @elseif($days < 0)
                <span class="text-rose-400 font-medium">hace {{ abs($days) }} día{{ abs($days)==1?'':'s' }}</span>
              @elseif($days === 0)
                <span class="text-amber-300 font-medium">hoy</span>
              @else
                <span class="text-sky-300 font-medium">en {{ $days }} día{{ $days==1?'':'s' }}</span>
              @endif
            </td>

            <td class="px-4 py-3 text-center">
              <x-status-badge 
                :status="$credit->status === 'pagado' ? 'aprobado' 
                        : ($credit->status === 'vencido' ? 'rechazado' 
                        : 'pendiente')" 
                :label="ucfirst($credit->status)" />
            </td>

            {{-- ===== Acciones ordenadas: Ver · Recibo · Eliminar ===== --}}
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-2">
                {{-- Ver --}}
                <a href="{{ route('credits.show', $credit) }}"
                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-sky-500/40 text-sky-300 hover:bg-sky-500/10 text-xs font-medium transition"
                   title="Ver detalle del crédito">
                  👁️ Ver
                </a>

                {{-- Recibo (si hay al menos un pago) --}}
                @if($lastPayment)
                  <a href="{{ route('payments.receipt', $lastPayment) }}"
                     target="_blank"
                     class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-black md:text-white text-xs font-semibold transition"
                     title="Imprimir último recibo">
                    🧾 Recibo
                  </a>
                @else
                  <button disabled
                          class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-800 text-slate-500 text-xs font-medium cursor-not-allowed"
                          title="Aún sin pagos">
                    🧾 Recibo
                  </button>
                @endif

                {{-- Eliminar --}}
                <form action="{{ route('credits.destroy', $credit) }}" method="POST"
                      onsubmit="return confirm('¿Eliminar definitivamente el crédito #{{ $credit->id }}?')">
                  @csrf @method('DELETE')
                  <button type="submit"
                          class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-rose-600/50 text-rose-300 hover:bg-rose-900/30 text-xs font-medium transition"
                          title="Eliminar crédito">
                    🗑️ Eliminar
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="px-6 py-10 text-center">
              <div class="text-slate-400">No se encontraron créditos con los filtros actuales.</div>
              <a href="{{ route('credits.index') }}"
                 class="inline-block mt-3 px-3 py-2 rounded-lg border border-slate-600 text-slate-200 bg-slate-800 hover:bg-slate-700 transition">
                Limpiar filtros
              </a>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="p-4 border-t border-slate-700">
    {{ $credits->onEachSide(1)->links() }}
  </div>
</div>
@endsection
