{{-- resources/views/sales/create.blade.php --}}
@extends('layout.admin')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<h1 class="text-2xl font-semibold text-black-200 mb-4">➕ Nueva venta</h1>

{{-- ⚠️ Errores de validación --}}
@if ($errors->any())
  <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
    <ul class="list-disc ml-5">
      @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

{{-- ⚠️ Alerta de stock / validación en tiempo real --}}
<div id="stockAlert" class="hidden mb-4 p-3 rounded bg-yellow-100 text-yellow-900"></div>

<form method="POST" action="{{ route('sales.store') }}" id="sale-form"
      class="bg-gray-900 text-white rounded shadow p-6 space-y-6">
  @csrf

  {{-- ================= CABECERA ================= --}}
  <div class="grid md:grid-cols-3 gap-4">
    {{-- Cliente con typeahead --}}
    <div class="relative">
      <label class="block text-sm text-gray-300 mb-1">Cliente</label>
      <input id="clientQuery" type="text" autocomplete="off"
             placeholder="Buscar por ID, nombre o RUC…"
             class="w-full rounded bg-gray-800 border border-gray-700 text-white px-3 py-2">
      <input type="hidden" name="client_id" id="clientId">
      <div id="clientBox"
           class="absolute z-20 mt-1 w-full max-h-56 overflow-auto rounded border border-gray-700 bg-gray-800 text-gray-100 shadow hidden"></div>
    </div>

    {{-- Modo de pago --}}
    <div>
      <label class="block text-sm text-gray-300 mb-1">Modo de pago</label>
      <select name="modo_pago" id="paymentMode" class="w-full rounded bg-gray-800 border border-gray-700 text-white px-3 py-2">
        <option value="contado">Contado</option>
        <option value="credito">Crédito</option>
      </select>
    </div>

    {{-- Fecha de venta --}}
    <div>
      <label class="block text-sm text-gray-300 mb-1">Fecha</label>
      <input type="date" name="fecha"
             class="w-full rounded bg-gray-800 border border-gray-700 text-white px-3 py-2"/>
    </div>

    {{-- Opciones de crédito (visible sólo si es crédito) --}}
    <div id="creditOptions" class="md:col-span-3 hidden">
      <div class="mt-2 p-3 rounded border border-indigo-600 bg-gray-800/60">
        <div class="grid sm:grid-cols-3 gap-4 items-end">
          <div>
            <label class="block text-sm text-gray-300 mb-1">📅 Primer vencimiento</label>
            <input type="date" name="primer_vencimiento" id="firstDueDate"
                   class="w-full rounded bg-gray-800 border border-gray-700 text-white px-3 py-2">
            <p class="text-xs text-gray-400 mt-1">
              Las demás cuotas se generan mensualmente a partir de esta fecha.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ================= ÍTEMS ================= --}}
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <h2 class="text-lg text-white font-semibold">Ítems</h2>
      <button type="button" id="add-row"
              class="px-3 py-1.5 rounded bg-blue-600 hover:bg-blue-700 text-white">➕ Agregar ítem</button>
    </div>

    <div class="overflow-x-visible">
      <table class="table-fixed w-full text-sm">
        <thead>
          <tr class="text-left text-gray-300 bg-gray-800/70">
            <th class="px-3 py-2 w-20">ID</th>
            <th class="px-3 py-2 w-64">Nombre</th>
            <th class="px-3 py-2 w-32"><span id="th-cuotas" class="hidden">Cuotas</span></th>
            <th class="px-3 py-2 w-28" id="th-precio">Precio</th>
            <th class="px-3 py-2 w-24">Cant.</th>
            <th class="px-3 py-2 w-28">IVA</th>
            <th class="px-3 py-2 text-right w-28">Subtotal</th>
            <th class="px-3 py-2 text-right w-12">—</th>
          </tr>
        </thead>
        <tbody id="items-body" class="divide-y divide-gray-700"></tbody>
      </table>
    </div>
  </div>

  {{-- ================= NOTA ================= --}}
  <div>
    <label class="block text-sm text-gray-300 mb-1">Nota</label>
    <textarea name="nota" rows="3"
      class="w-full rounded bg-gray-800 border border-gray-700 text-white px-3 py-2"></textarea>
  </div>

  {{-- ================= TOTALES ================= --}}
  <div class="grid md:grid-cols-2 gap-4">
    <div></div>
    <div class="rounded border border-gray-700 p-4 text-gray-200 bg-gray-800/70 space-y-1">
      <div class="flex justify-between"><span>Gravada 10%:</span><span id="grav10">Gs. 0</span></div>
      <div class="flex justify-between"><span>IVA 10%:</span><span id="iva10">Gs. 0</span></div>
      <div class="flex justify-between"><span>Gravada 5%:</span><span id="grav5">Gs. 0</span></div>
      <div class="flex justify-between"><span>IVA 5%:</span><span id="iva5">Gs. 0</span></div>
      <div class="flex justify-between"><span>Exento:</span><span id="exento">Gs. 0</span></div>
      <div class="border-t border-gray-600 my-2"></div>
      <div class="flex justify-between font-semibold text-white text-lg"><span>Total IVA:</span><span id="totIva">Gs. 0</span></div>
      <div class="flex justify-between text-xl font-bold text-white"><span>Total:</span><span id="totGen">Gs. 0</span></div>
    </div>
  </div>

  {{-- ================= ACCIONES ================= --}}
  <div class="flex gap-3">
    <button id="btn-guardar" class="px-4 py-2 rounded bg-green-600 hover:bg-green-700 text-white disabled:opacity-60">💾 Guardar</button>
    <a href="{{ route('sales.index') }}" class="px-4 py-2 rounded border border-gray-600 text-gray-300 hover:bg-gray-700">Cancelar</a>
  </div>
</form>

{{-- ================= TEMPLATE FILA DE ÍTEM ================= --}}
<template id="row-template">
  <tr class="text-gray-200">
    <td class="px-3 py-2">
      <input type="number" min="1" class="pid w-20 rounded bg-gray-800 border border-gray-700 px-2 py-1" placeholder="ID">
      <div class="bad text-xs text-red-300 mt-1 hidden"></div>
    </td>
    <td class="px-3 py-2 relative">
      <input type="text" class="pname w-64 rounded bg-gray-800 border border-gray-700 px-2 py-1" placeholder="Nombre o buscar…">
      <div class="suggest absolute z-20 mt-1 w-80 max-h-64 overflow-auto rounded border border-gray-700 bg-gray-800 text-gray-100 shadow hidden"></div>
    </td>
    {{-- Selector de cuotas (oculto salvo "Crédito") --}}
    <td class="px-3 py-2">
      <select class="plan w-32 rounded bg-gray-800 border border-gray-700 px-2 py-1 hidden"></select>
    </td>
    <td class="px-3 py-2 price-td">
      <input type="number" step="0.01" min="0" class="price w-28 text-right rounded bg-gray-800 border border-gray-700 px-2 py-1" value="0">
    </td>
    <td class="px-3 py-2">
      <input type="number" min="1" class="qty w-20 text-right rounded bg-gray-800 border border-gray-700 px-2 py-1" value="1">
      <div class="warn text-xs text-yellow-300 mt-1 hidden"></div>
    </td>
    <td class="px-3 py-2">
      <select class="iva w-28 rounded bg-gray-800 border border-gray-700 px-2 py-1">
        <option value="10">IVA 10%</option>
        <option value="5">IVA 5%</option>
        <option value="exento">Exento</option>
      </select>
    </td>
    <td class="px-3 py-2 text-right"><span class="subtotal">Gs. 0</span></td>
    <td class="px-3 py-2 text-right">
      <button type="button" class="del px-2 py-1 rounded border border-red-600/40 text-red-400 hover:bg-red-900/30">✕</button>
    </td>
  </tr>
</template>

@php
  $clientSearchUrl  = route('clients.search');
  $productSearchUrl = route('products.search');
  $productFindById  = route('products.findById', ['id' => 0]); // /api/products/id/0
  $stockCheckUrl    = route('stock.check');
@endphp

<script>
(function(){
  const fmt = n => 'Gs. ' + (Math.round(n).toLocaleString('es-PY'));
  const debounce = (fn,ms=250)=>{ let t; return(...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };

  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const stockAlert = document.getElementById('stockAlert');
  const btnGuardar = document.getElementById('btn-guardar');
  const paymentMode = document.getElementById('paymentMode');
  const thCuotas = document.getElementById('th-cuotas');
  const thPrecio = document.getElementById('th-precio');

  // ---------- Opciones de crédito (UI) ----------
  const creditOptions = document.getElementById('creditOptions');
  const firstDueDate  = document.getElementById('firstDueDate');
  const saleDateInput = document.querySelector('input[name="fecha"]');

  function nextMonthSameDay(date) {
    const d = new Date(date.getTime());
    const day = d.getDate();
    d.setMonth(d.getMonth() + 1);
    if (d.getDate() < day) d.setDate(0);
    return d;
  }
  function fmtDateYYYYMMDD(d) {
    const yyyy = d.getFullYear();
    const mm   = String(d.getMonth() + 1).padStart(2,'0');
    const dd   = String(d.getDate()).padStart(2,'0');
    return `${yyyy}-${mm}-${dd}`;
  }
  function syncFirstDueDate() {
    const f = saleDateInput.value ? new Date(saleDateInput.value) : new Date();
    const first = nextMonthSameDay(f);
    if (firstDueDate && !firstDueDate.value) {
      firstDueDate.value = fmtDateYYYYMMDD(first);
    }
  }
  function toggleCreditOptions() {
    const isCredit = paymentMode.value === 'credito';
    creditOptions.classList.toggle('hidden', !isCredit);
    if (isCredit && !firstDueDate.value) syncFirstDueDate();
  }

  // ====== NUEVO: flags para no alertar antes de tiempo ======
  let formTouched = false;
  function markTouched(tr){ tr.dataset.touched = '1'; formTouched = true; }

  // ---------- CLIENTE ----------
  const clientQuery=document.getElementById('clientQuery');
  const clientId=document.getElementById('clientId');
  const clientBox=document.getElementById('clientBox');

  function renderClients(items){
    if(!items.length){ clientBox.classList.add('hidden'); return; }
    clientBox.innerHTML=items.map((c,i)=>`
      <div data-i="${i}" class="cPick px-3 py-2 hover:bg-gray-700 cursor-pointer">
        <div>${c.name}</div>
        <div class="text-xs text-gray-400">ID: ${c.id} · ${c.ruc??'—'} · ${c.email??''}</div>
      </div>`).join('');
    clientBox.classList.remove('hidden');
    clientBox.querySelectorAll('.cPick').forEach(n=>{
      n.addEventListener('mousedown',e=>{
        e.preventDefault();
        const c=items[n.dataset.i];
        clientQuery.value=`${c.name} (ID:${c.id})`;
        clientId.value=c.id;
        clientBox.classList.add('hidden');
      });
    });
  }

  const searchClients=debounce(async()=>{
    const q=clientQuery.value.trim();
    clientId.value='';
    if(q.length<1){ clientBox.classList.add('hidden'); return; }
    const res=await fetch(@json($clientSearchUrl)+'?q='+encodeURIComponent(q));
    renderClients(res.ok?await res.json():[]);
  },300);

  clientQuery.addEventListener('input',searchClients);
  clientQuery.addEventListener('focus',searchClients);
  clientQuery.addEventListener('blur',()=>setTimeout(()=>clientBox.classList.add('hidden'),120));

  // ---------- ÍTEMS ----------
  const body=document.getElementById('items-body');
  const tpl=document.getElementById('row-template');
  const add=document.getElementById('add-row');

  function recalc(){
    let g10=0,i10=0,g5=0,i5=0,ex=0;
    body.querySelectorAll('tr').forEach(tr=>{
      const price=+tr.querySelector('.price').value||0;
      const qty=+tr.querySelector('.qty').value||0;
      const iva=tr.querySelector('.iva').value;
      const line=price*qty;
      tr.querySelector('.subtotal').textContent=fmt(line);
      if(iva==='10'){ let grav=line/1.1; g10+=grav; i10+=line-grav; }
      else if(iva==='5'){ let grav=line/1.05; g5+=grav; i5+=line-grav; }
      else ex+=line;
    });
    const totIva=i10+i5, total=g10+g5+ex+totIva;
    document.getElementById('grav10').textContent=fmt(g10);
    document.getElementById('iva10').textContent=fmt(i10);
    document.getElementById('grav5').textContent=fmt(g5);
    document.getElementById('iva5').textContent=fmt(i5);
    document.getElementById('exento').textContent=fmt(ex);
    document.getElementById('totIva').textContent=fmt(totIva);
    document.getElementById('totGen').textContent=fmt(total);
  }

  // ====== Helpers cuotas / precio contado visible ======
  function updateHeaderCuotas(){
    let anyVisible = false;
    body.querySelectorAll('tr').forEach(tr=>{
      if(paymentMode.value==='credito' && (tr._plans||[]).length){ anyVisible = true; }
    });
    thCuotas?.classList.toggle('hidden', !anyVisible);
  }

  function togglePrecioColumn(){
    const hide = paymentMode.value === 'credito';
    thPrecio?.classList.toggle('hidden', hide);
    body.querySelectorAll('tr').forEach(tr=>{
      const td = tr.querySelector('.price-td');
      const inp = tr.querySelector('.price');
      if(!td || !inp) return;
      td.classList.toggle('hidden', hide);
      inp.readOnly = hide; // en crédito no editable
    });
  }

  function showPlansIfCredit(tr){
    const sel = tr.querySelector('.plan');
    if(paymentMode.value === 'credito' && (tr._plans||[]).length){
      sel.classList.remove('hidden');
    }else{
      sel.classList.add('hidden');
    }
    updateHeaderCuotas();
    togglePrecioColumn();
  }

  function renderPlans(tr, plans){
    tr._plans = plans || [];
    const sel = tr.querySelector('.plan');
    sel.innerHTML = '';
    if(!tr._plans.length){
      sel.classList.add('hidden');
      updateHeaderCuotas();
      togglePrecioColumn();
      return;
    }
    sel.innerHTML = tr._plans.map(p=>{
      const cuota = Number(p.installments)||0;
      const precioCuota = Number(p.installment_price)||0;
      return `<option value="${cuota}|${precioCuota}">${cuota} x ${fmt(precioCuota)}</option>`;
    }).join('');
    showPlansIfCredit(tr);
    sel.dispatchEvent(new Event('change'));   // dispara el cálculo
  }

  function attachPlanChange(tr){
    const sel = tr.querySelector('.plan');
    sel.addEventListener('change', ()=>{
      const val = sel.value || '';
      const parts = val.split('|').map(Number);
      const n = parts[0]||0;
      const pricePer = parts[1]||0;
      if(paymentMode.value === 'credito' && n>0 && pricePer>0){
        tr.querySelector('.price').value = n * pricePer; // total por unidad
      }else{
        if(typeof tr._cashPrice === 'number'){
          tr.querySelector('.price').value = tr._cashPrice;
        }
      }
      recalc(); scheduleCheck();
    });
  }

  // ====== API productos ======
  async function fetchProductById(id){
    if(!id) return null;
    const url = @json($productFindById).replace(/0$/, String(id));
    const res = await fetch(url);
    if(!res.ok) return null;
    return res.json();
  }
  async function searchProducts(q){
    const res=await fetch(@json($productSearchUrl)+'?q='+encodeURIComponent(q));
    return res.ok?await res.json():[];
  }

  // ====== Verificación stock (con “tocado”) ======
  function collectItems(){
    return Array.from(body.querySelectorAll('tr')).map(tr=>{
      const idVal = tr.querySelector('.pid').value;
      const product_id = parseInt(idVal || '0', 10);
      const qty = parseInt(tr.querySelector('.qty').value || '0', 10);
      return { tr, product_id, qty,
        bad: tr.querySelector('.bad'),
        warn: tr.querySelector('.warn') };
    });
  }

  let t;
  function scheduleCheck(){ clearTimeout(t); t=setTimeout(checkStock, 300); }

  async function checkStock(){
    const rows = collectItems();

    stockAlert.classList.add('hidden'); stockAlert.textContent='';
    rows.forEach(x=>{
      x.warn.classList.add('hidden'); x.warn.textContent='';
      x.bad.classList.add('hidden');  x.bad.textContent='';
      x.tr.classList.remove('ring-2','ring-red-400');
    });

    // Si nadie tocó nada y no hay nada escrito, no molestamos
    const anyTyped = rows.some(x=>{
      const idStr  = (x.tr.querySelector('.pid').value || '').trim();
      const nameStr= (x.tr.querySelector('.pname').value || '').trim();
      return idStr.length>0 || nameStr.length>0;
    });
    if(!formTouched && !anyTyped){
      btnGuardar.disabled = true;
      return;
    }

    // Sólo marcamos filas tocadas con datos incompletos
    const unresolved = rows.filter(x => {
      const idStr  = (x.tr.querySelector('.pid').value || '').trim();
      const nameStr= (x.tr.querySelector('.pname').value || '').trim();
      const touched= x.tr.dataset.touched === "1";
      return !(x.product_id > 0) && touched && (idStr.length>0 || nameStr.length>0);
    });

    if(unresolved.length){
      unresolved.forEach(x=>{
        x.bad.textContent = 'Ingresá un ID válido y confirmá.';
        x.bad.classList.remove('hidden');
        x.tr.classList.add('ring-2','ring-red-400');
      });
      btnGuardar.disabled = true;
      stockAlert.textContent = '⚠️ Hay ítems sin producto válido.';
      stockAlert.classList.remove('hidden');
      return;
    }

    const list = rows.filter(x => x.product_id>0 && x.qty>0);
    if(list.length===0){ btnGuardar.disabled = true; return; }

    try{
      const res = await fetch(@json($stockCheckUrl), {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ items: list.map(x=>({product_id:x.product_id, qty:x.qty})) })
      });
      const data = await res.json();

      if(!data.ok){
        btnGuardar.disabled = true;
        data.shortages.forEach(s=>{
          const row = rows.find(x=>x.product_id===s.product_id);
          if(row){
            row.warn.textContent = `Stock: ${s.stock}. Máx. vendible: ${s.max_sell}.`;
            row.warn.classList.remove('hidden');
            row.tr.classList.add('ring-2','ring-red-400');
          }
        });
        stockAlert.textContent = '⚠️ Stock insuficiente. Ajustá cantidades.';
        stockAlert.classList.remove('hidden');
      }else{
        btnGuardar.disabled = false;
      }
    }catch(e){
      console.error(e);
      btnGuardar.disabled = true;
      stockAlert.textContent = '⚠️ No se pudo verificar el stock. Reintentá.';
      stockAlert.classList.remove('hidden');
    }
  }

  // ====== Wiring de una fila ======
  function wireRow(tr){
    // marcar como tocado al escribir
    const pid=tr.querySelector('.pid');
    const pname=tr.querySelector('.pname');
    pid.addEventListener('input', ()=>{ markTouched(tr); });
    pname.addEventListener('input', ()=>{ markTouched(tr); });

    tr.querySelectorAll('.price,.qty,.iva').forEach(inp=>{
      inp.addEventListener('input', ()=>{ markTouched(tr); recalc(); scheduleCheck(); });
    });
    tr.querySelector('.del').addEventListener('click',()=>{ tr.remove(); recalc(); scheduleCheck(); updateHeaderCuotas(); });

    attachPlanChange(tr); // escuchar cambios del select de cuotas

    // blur de ID → carga producto
    pid.addEventListener('blur', async ()=>{
      const id = parseInt(pid.value || '0', 10);
      const bad = tr.querySelector('.bad');
      if(!(id>0)){
        bad.textContent = 'ID inválido';
        bad.classList.remove('hidden');
        tr.classList.add('ring-2','ring-red-400');
        tr._plans = [];
        showPlansIfCredit(tr);
        scheduleCheck();
        return;
      }
      const p = await fetchProductById(id);
      if(!p){
        bad.textContent = 'Producto no encontrado';
        bad.classList.remove('hidden');
        tr.classList.add('ring-2','ring-red-400');
        tr._plans = [];
        showPlansIfCredit(tr);
        scheduleCheck();
        return;
      }
      tr.querySelector('.pname').value = p.name ?? '';
      tr.querySelector('.price').value = p.price_cash ?? 0;
      tr._cashPrice = +p.price_cash || 0;      // guardar contado
      renderPlans(tr, p.installments || []);   // cargar planes
      bad.classList.add('hidden');
      tr.classList.remove('ring-2','ring-red-400');

      showPlansIfCredit(tr);
      recalc(); scheduleCheck();
    });

    // Autocomplete por nombre
    const box=tr.querySelector('.suggest');
    const doSearch=debounce(async()=>{
      if(pname.value.trim().length<1){ box.classList.add('hidden'); return; }
      const items=await searchProducts(pname.value.trim());
      if(!items.length){ box.classList.add('hidden'); return; }
      box.innerHTML=items.map((p,i)=>`
        <div data-i="${i}" class="pick px-3 py-2 hover:bg-gray-700 cursor-pointer">
          ID:${p.id} · ${p.name} · ${fmt(p.price_cash||0)}
        </div>`).join('');
      box.classList.remove('hidden');
      box.querySelectorAll('.pick').forEach(n=>{
        n.addEventListener('mousedown',async e=>{
          e.preventDefault();
          const p=items[n.dataset.i];
          pid.value=p.id;
          pname.value=p.name;
          tr.querySelector('.price').value=p.price_cash||0;
          tr._cashPrice = +p.price_cash || 0;
          const pDetail = await fetchProductById(p.id);
          renderPlans(tr, (pDetail && pDetail.installments) ? pDetail.installments : []);
          tr.querySelector('.bad').classList.add('hidden');
          tr.classList.remove('ring-2','ring-red-400');
          box.classList.add('hidden');

          showPlansIfCredit(tr);
          recalc(); scheduleCheck();
        });
      });
    },300);
    pname.addEventListener('input',doSearch);
    pname.addEventListener('focus',doSearch);
    pname.addEventListener('blur',()=>setTimeout(()=>box.classList.add('hidden'),120));
  }

  function addRow(){
    const n=tpl.content.cloneNode(true);
    body.appendChild(n);
    const tr = body.lastElementChild;
    wireRow(tr);
    recalc();              // evita alerta inicial
    updateHeaderCuotas();
    togglePrecioColumn();
  }
  document.getElementById('add-row').addEventListener('click',addRow);
  addRow();

  // ====== Cambio de modo de pago ======
  paymentMode.addEventListener('change', ()=>{
    body.querySelectorAll('tr').forEach(tr=>{
      showPlansIfCredit(tr);
      if(paymentMode.value !== 'credito' && typeof tr._cashPrice === 'number'){
        tr.querySelector('.price').value = tr._cashPrice;
      }
    });
    togglePrecioColumn();
    recalc(); scheduleCheck();
    toggleCreditOptions();
  });

  // ====== Serializar ítems ======
  document.getElementById('sale-form').addEventListener('submit',e=>{
    if(btnGuardar.disabled){ e.preventDefault(); return; }

    // limpiar restos previos
    [...document.querySelectorAll('input[name^="items["]')].forEach(n=>n.remove());

    let idx=0;
    body.querySelectorAll('tr').forEach(tr=>{
      const id  = parseInt(tr.querySelector('.pid').value || '0', 10);
      const name= tr.querySelector('.pname').value;
      const price=tr.querySelector('.price').value;
      const qty = tr.querySelector('.qty').value;
      const iva = tr.querySelector('.iva').value;

      if(!(id>0) || qty<=0) return;

      const addH=(n,v)=>{ const h=document.createElement('input'); h.type='hidden'; h.name=`items[${idx}][${n}]`; h.value=v; e.target.appendChild(h); };
      addH('product_id',id);
      addH('product_name',name);
      addH('unit_price',price);              // contado o total crédito por unidad (según modo)
      addH('qty',qty);
      addH('iva_type',iva);

      // mandar info de cuotas si corresponde
      const sel = tr.querySelector('.plan');
      if(paymentMode.value==='credito' && sel && sel.value){
        const [cuotas, precioCuota] = sel.value.split('|');
        addH('installments', cuotas);
        addH('installment_price', precioCuota);
      }
      idx++;
    });

    if(!document.getElementById('clientId').value){
      e.preventDefault(); alert('Seleccioná un cliente de la lista');
    }
    if(idx===0){
      e.preventDefault(); alert('Agregá al menos un ítem');
    }
  });

  // ====== Fecha por defecto + UI crédito ======
  document.addEventListener("DOMContentLoaded", () => {
    const fechaInput = document.querySelector('input[name="fecha"]');
    if (fechaInput && !fechaInput.value) {
      const hoy = new Date();
      const yyyy = hoy.getFullYear();
      const mm = String(hoy.getMonth() + 1).padStart(2, '0');
      const dd = String(hoy.getDate()).padStart(2, '0');
      fechaInput.value = `${yyyy}-${mm}-${dd}`;
    }
    toggleCreditOptions();
    if (paymentMode.value === 'credito') syncFirstDueDate();
  });

  // sincronizar primer vencimiento al cambiar fecha (solo si vacío)
  saleDateInput.addEventListener('change', () => {
    if (!firstDueDate.value) syncFirstDueDate();
  });

})();
</script>
@endsection
