{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layout.admin')

@section('content')
@php
  if (!function_exists('money_gs')) {
    function money_gs($n){ return 'Gs. '.number_format((float)$n,0,',','.'); }
  }
@endphp

<div class="mb-6">
  <h1 class="text-2xl font-bold text-slate-100">📊 Panel del Administrador</h1>
  <p class="text-slate-400 text-sm">
    Resumen general de ventas, compras, cobros, cuentas por cobrar y actividad reciente
  </p>
</div>

{{-- 🔍 FILTROS --}}
<form id="filtros" class="bg-slate-900 border border-slate-700 rounded-xl p-4 mb-6">
  <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
    <div class="md:col-span-2">
      <label class="block text-xs text-slate-400 mb-1">Desde</label>
      <input type="date" name="from" value="{{ $from ?? now()->startOfMonth()->toDateString() }}"
             class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-slate-200">
    </div>
    <div class="md:col-span-2">
      <label class="block text-xs text-slate-400 mb-1">Hasta</label>
      <input type="date" name="to" value="{{ $to ?? now()->toDateString() }}"
             class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-slate-200">
    </div>

    <div class="md:col-span-2 flex gap-2">
      <button type="button" data-range="month"
        class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-600 text-slate-200 hover:bg-slate-700 transition">
        Mes actual
      </button>
      <button type="button" data-range="week"
        class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-600 text-slate-200 hover:bg-slate-700 transition">
        Últimos 7 días
      </button>
      <button id="btn-aplicar" type="submit"
        class="ml-auto px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition">
        Aplicar
      </button>
    </div>
  </div>
</form>

{{-- 💰 TARJETAS DE INDICADORES --}}
<div id="kpis" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-3 mb-6">
  @foreach ([
      ['Ventas','ventas_total'],
      ['Compras','compras_total'],
      ['Cobrado','pagos_total'],
      ['CxC (Saldo)','cxc_saldo'],
      ['Descuentos','descuentos'],
      ['Margen Bruto','margen']
    ] as $k)
  <div class="bg-slate-900 border border-slate-700 rounded-xl p-4 text-center">
    <div class="text-xs uppercase text-slate-400">{{ $k[0] }}</div>
    <div class="text-xl font-semibold text-slate-100 mt-1" data-key="{{ $k[1] }}">Gs. 0</div>
  </div>
  @endforeach
</div>

{{-- 📈 GRÁFICOS + WIDGETS --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
  <div class="bg-slate-900 border border-slate-700 rounded-xl p-4">
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-slate-200 font-semibold">Flujo de Cobros</h3>
      <span id="sum-cobros" class="text-xs text-emerald-300">Gs. 0</span>
    </div>
    <canvas id="chartFlujo" height="110"></canvas>
  </div>

  <div class="bg-slate-900 border border-slate-700 rounded-xl p-4">
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-slate-200 font-semibold">Ventas vs Compras</h3>
    </div>
    <canvas id="chartVC" height="110"></canvas>
  </div>

  <div class="bg-slate-900 border border-slate-700 rounded-xl p-4">
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-slate-200 font-semibold">Créditos por estado</h3>
      <span id="cxc-count" class="text-xs text-slate-400">0 créditos</span>
    </div>
    <canvas id="chartCXC" height="110"></canvas>
  </div>
</div>

{{-- 🧍‍♂️ TOPS + ACTIVIDAD --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
  <div class="bg-slate-900 border border-slate-700 rounded-xl p-4">
    <h3 class="text-slate-200 font-semibold mb-3">Top Clientes con Mayor Saldo</h3>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-800 text-slate-300 uppercase text-[11px]">
          <tr>
            <th class="px-3 py-2 text-left">Cliente</th>
            <th class="px-3 py-2 text-left">CI/RUC</th>
            <th class="px-3 py-2 text-right">Saldo</th>
          </tr>
        </thead>
        <tbody id="topClientes" class="divide-y divide-slate-800 text-slate-100">
          <tr><td class="px-3 py-3 text-center" colspan="3">Cargando datos...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="bg-slate-900 border border-slate-700 rounded-xl p-4">
    <h3 class="text-slate-200 font-semibold mb-3">Actividad reciente</h3>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-800 text-slate-300 uppercase text-[11px]">
          <tr>
            <th class="px-3 py-2 text-left">Fecha</th>
            <th class="px-3 py-2 text-left">Tipo</th>
            <th class="px-3 py-2 text-left">Referencia</th>
            <th class="px-3 py-2 text-right">Monto</th>
          </tr>
        </thead>
        <tbody id="actividad" class="divide-y divide-slate-800 text-slate-100">
          <tr><td class="px-3 py-3 text-center" colspan="4">Cargando datos...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

@can('view-finance')
  <form method="POST" action="{{ route('finance.lock') }}" class="mt-6">
    @csrf
    <button type="submit"
            class="px-4 py-2 rounded-lg bg-slate-800 text-slate-100 border border-slate-600 hover:bg-slate-700">
      🔐 Bloquear (PIN)
    </button>
  </form>
@endcan

{{-- 📊 Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
const fmt = n => new Intl.NumberFormat('es-PY').format(Number(n||0));

// Rango rápido
function rango(btn){
  const from = document.querySelector('input[name="from"]');
  const to   = document.querySelector('input[name="to"]');
  const now = new Date();
  if(btn.dataset.range === 'month'){
    const first = new Date(now.getFullYear(), now.getMonth(), 1);
    from.value = first.toISOString().slice(0,10);
    to.value   = now.toISOString().slice(0,10);
  }
  if(btn.dataset.range === 'week'){
    const seven = new Date(Date.now() - 6*24*3600*1000);
    from.value = seven.toISOString().slice(0,10);
    to.value   = now.toISOString().slice(0,10);
  }
}
document.querySelectorAll('button[data-range]').forEach(b=> b.addEventListener('click', ()=> rango(b)));

let chartFlujo, chartVC, chartCXC;

function setKPIsZeros(){
  document.querySelectorAll('#kpis [data-key]').forEach(el => el.textContent = 'Gs. 0');
  document.getElementById('sum-cobros').textContent = 'Gs. 0';
  document.getElementById('cxc-count').textContent  = '0 créditos';
}
function setLoadingTB(id, cols){
  const tb = document.getElementById(id);
  tb.innerHTML = `<tr><td class="px-3 py-3 text-center" colspan="${cols}">Cargando datos...</td></tr>`;
}
function setErrorTB(id, cols, msg='Error al cargar'){
  const tb = document.getElementById(id);
  tb.innerHTML = `<tr><td class="px-3 py-3 text-center text-rose-400" colspan="${cols}">${msg}</td></tr>`;
}

async function cargar(){
  const form = document.getElementById('filtros');
  const btnAplicar = document.getElementById('btn-aplicar');
  const params = new URLSearchParams(new FormData(form));

  btnAplicar?.setAttribute('disabled', 'disabled');
  setLoadingTB('topClientes', 3);
  setLoadingTB('actividad', 4);

  try {
    // Si tu endpoint tiene otro name (p. ej. finance.stats), cámbialo aquí:
    const res = await fetch(`{{ finance.stats') }}?${params.toString()}`, {
      headers: { 'Accept': 'application/json' },
    });

    const ct = res.headers.get('content-type') || '';
    if(!res.ok) throw new Error(`HTTP ${res.status}`);
    if(!ct.includes('application/json')) throw new Error('Respuesta no JSON');

    const j = await res.json();

    // ===== KPIs =====
    const ventas_total  = Number(j.ventas_total ?? 0);
    const compras_total = Number(j.compras_total ?? 0);
    const pagos_total   = Number(j.pagos_total ?? 0);
    const cxc_saldo     = Number(j.cxc_saldo ?? 0);
    const descuentos    = Number(j.descuentos ?? 0);
    const margen_calc   = ventas_total - compras_total - descuentos;

    const map = { ventas_total, compras_total, pagos_total, cxc_saldo, descuentos, margen: margen_calc };
    document.querySelectorAll('#kpis [data-key]').forEach(el=>{
      const k = el.getAttribute('data-key');
      el.textContent = 'Gs. ' + fmt(map[k] ?? 0);
    });
    document.getElementById('sum-cobros').textContent = 'Gs. ' + fmt(pagos_total);

    // ===== Top Clientes =====
    const tbTop = document.getElementById('topClientes');
    tbTop.innerHTML = '';
    (Array.isArray(j.topClientes) ? j.topClientes : []).forEach(r=>{
      tbTop.insertAdjacentHTML('beforeend', `
        <tr class="hover:bg-slate-800/40 transition">
          <td class="px-3 py-2">${r.name ?? '—'}</td>
          <td class="px-3 py-2">${r.ruc ?? '—'}</td>
          <td class="px-3 py-2 text-right text-emerald-400">Gs. ${fmt(r.saldo ?? 0)}</td>
        </tr>
      `);
    });
    if(tbTop.children.length === 0){
      tbTop.innerHTML = '<tr><td class="px-3 py-3 text-slate-400 italic text-center" colspan="3">Sin resultados</td></tr>';
    }

    // ===== Actividad =====
    const tbAct = document.getElementById('actividad');
    tbAct.innerHTML = '';
    (Array.isArray(j.actividad) ? j.actividad : []).forEach(r=>{
      tbAct.insertAdjacentHTML('beforeend', `
        <tr class="hover:bg-slate-800/40 transition">
          <td class="px-3 py-2 whitespace-nowrap">${r.fecha ?? '—'}</td>
          <td class="px-3 py-2">
            <span class="px-2 py-0.5 text-xs rounded
              ${
                (r.tipo ?? '').toLowerCase() === 'venta'   ? 'bg-emerald-600/15 text-emerald-300 border border-emerald-700/40' :
                (r.tipo ?? '').toLowerCase() === 'compra'  ? 'bg-sky-600/15 text-sky-300 border border-sky-700/40' :
                (r.tipo ?? '').toLowerCase() === 'pago'    ? 'bg-indigo-600/15 text-indigo-300 border border-indigo-700/40' :
                                                             'bg-slate-700/20 text-slate-200 border border-slate-600/40'
              }">
              ${r.tipo ?? '—'}
            </span>
          </td>
          <td class="px-3 py-2">${r.ref ?? '—'}</td>
          <td class="px-3 py-2 text-right">${r.monto != null ? 'Gs. '+fmt(r.monto) : '—'}</td>
        </tr>
      `);
    });
    if(tbAct.children.length === 0){
      tbAct.innerHTML = '<tr><td class="px-3 py-3 text-slate-400 italic text-center" colspan="4">Sin actividad</td></tr>';
    }

    // ===== Gráficos =====
    // Flujo (línea)
    const serieFlujo = Array.isArray(j.flujo) ? j.flujo : [];
    const lblFlujo = serieFlujo.map(x => x.fecha);
    const valFlujo = serieFlujo.map(x => Number(x.cobrado || 0));
    chartFlujo?.destroy();
    chartFlujo = new Chart(document.getElementById('chartFlujo'), {
      type: 'line',
      data: { labels: lblFlujo, datasets: [{ label: 'Cobrado', data: valFlujo, borderColor: '#10b981', tension: .3 }] },
      options: {
        plugins: { legend: { labels: { color: '#cbd5e1' } } },
        scales: {
          x: { ticks: { color:'#94a3b8' }, grid: { color:'#1e293b' } },
          y: { ticks: { color:'#94a3b8', callback:v=>'Gs. '+fmt(v) }, grid: { color:'#1e293b' } }
        }
      }
    });

    // Ventas vs Compras (barras)
    const serieVC = Array.isArray(j.vc) ? j.vc : []; // [{fecha, ventas, compras}]
    const lblVC = serieVC.map(x => x.fecha);
    const valV  = serieVC.map(x => Number(x.ventas  || 0));
    const valC  = serieVC.map(x => Number(x.compras || 0));
    chartVC?.destroy();
    chartVC = new Chart(document.getElementById('chartVC'), {
      type: 'bar',
      data: {
        labels: lblVC,
        datasets: [
          { label:'Ventas',  data: valV, backgroundColor:'#10b981' },
          { label:'Compras', data: valC, backgroundColor:'#38bdf8' }
        ]
      },
      options: {
        plugins: { legend: { labels: { color:'#cbd5e1' } } },
        scales: {
          x: { ticks:{ color:'#94a3b8' }, grid:{ color:'#1e293b' } },
          y: { ticks:{ color:'#94a3b8', callback:v=>'Gs. '+fmt(v) }, grid:{ color:'#1e293b' } }
        }
      }
    });

    // Créditos por estado (doughnut)
    const cxc = j.cxc_estados || {}; // {vigente: n, vencido: n, pagado: n}
    const labelsCXC = Object.keys(cxc);
    const dataCXC   = Object.values(cxc).map(Number);
    document.getElementById('cxc-count').textContent = `${dataCXC.reduce((a,b)=>a+b,0)} créditos`;
    chartCXC?.destroy();
    chartCXC = new Chart(document.getElementById('chartCXC'), {
      type: 'doughnut',
      data: {
        labels: labelsCXC.map(x => x.charAt(0).toUpperCase()+x.slice(1)),
        datasets: [{ data: dataCXC, backgroundColor: ['#10b981','#f59e0b','#f43f5e'] }]
      },
      options: { plugins: { legend: { labels: { color:'#cbd5e1' } } } }
    });

  } catch (e) {
    console.error('dashboard.stats error', e);
    setKPIsZeros();
    setErrorTB('topClientes', 3);
    setErrorTB('actividad', 4);
    chartFlujo?.destroy(); chartVC?.destroy(); chartCXC?.destroy();
  } finally {
    btnAplicar?.removeAttribute('disabled');
  }
}

document.getElementById('filtros').addEventListener('submit', (e)=>{
  e.preventDefault(); cargar();
});
window.addEventListener('load', cargar);
</script>
@endsection
