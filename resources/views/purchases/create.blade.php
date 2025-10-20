{{-- resources/views/purchases/create.blade.php --}}
@extends('layout.admin')

@section('content')
<h1 class="text-2xl font-semibold text-blue-400 mb-4">➕ Nueva compra</h1>

@if ($errors->any())
  <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
    <ul class="list-disc ml-5">
      @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
  </div>
@endif

{{-- 🔧 utilidades locales para inputs number --}}
@push('styles')
<style>
  input.no-spinner::-webkit-outer-spin-button,
  input.no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
  input.no-spinner { -moz-appearance: textfield; } /* Firefox */
</style>
@endpush

<form method="POST" action="{{ route('purchases.store') }}"
      class="bg-gray-900 text-white rounded shadow p-6 space-y-6"
      x-data="purchaseForm()">
  @csrf

  {{-- ================== PROVEEDOR ================== --}}
  <div class="grid md:grid-cols-2 gap-4" x-data="supplierSearch()">
    <div class="relative">
      <label class="block mb-1 font-medium text-gray-300">Proveedor</label>
      <input type="text"
             x-model="query"
             x-on:input.debounce.300ms="search()"
             x-on:keydown.escape="open=false"
             x-on:focus="if(results.length) open=true"
             placeholder="Buscar proveedor por nombre, RUC o ID…"
             class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700 focus:ring-2 focus:ring-blue-500"
             autocomplete="off">

      {{-- Dropdown --}}
      <div x-show="open" x-transition
           class="absolute z-20 mt-1 w-full max-h-60 overflow-auto rounded border border-gray-700 bg-white text-gray-900 shadow"
           @click.outside="open=false">
        <template x-if="loading">
          <div class="px-3 py-2 text-sm text-gray-500">Cargando…</div>
        </template>
        <template x-if="!loading && results.length === 0 && query.length > 1">
          <div class="px-3 py-2 text-sm text-gray-500">No se encontraron resultados</div>
        </template>
        <template x-for="item in results" :key="item.id">
          <button type="button" class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm"
                  @click="select(item)">
            <div class="font-medium" x-text="item.name"></div>
            <div class="text-xs text-gray-500"
                 x-text="`RUC: ${item.ruc ?? '—'} • Tel: ${item.phone ?? ''}`"></div>
          </button>
        </template>
      </div>

      <input type="hidden" name="supplier_id" :value="selected?.id ?? ''">
    </div>

    {{-- Campos autocompletados --}}
    <div class="grid md:grid-cols-2 gap-4 mt-4">
      <div>
        <label class="block mb-1 font-medium text-gray-300">RUC</label>
        <input type="text" x-model="selected?.ruc" readonly
               class="w-full px-3 py-2 rounded bg-gray-700 text-gray-300">
      </div>
      <div>
        <label class="block mb-1 font-medium text-gray-300">Teléfono</label>
        <input type="text" x-model="selected?.phone" readonly
               class="w-full px-3 py-2 rounded bg-gray-700 text-gray-300">
      </div>
      <div>
        <label class="block mb-1 font-medium text-gray-300">Email</label>
        <input type="text" x-model="selected?.email" readonly
               class="w-full px-3 py-2 rounded bg-gray-700 text-gray-300">
      </div>
      <div>
        <label class="block mb-1 font-medium text-gray-300">Dirección</label>
        <input type="text" x-model="selected?.address" readonly
               class="w-full px-3 py-2 rounded bg-gray-700 text-gray-300">
      </div>
    </div>
  </div>

  {{-- ================== FACTURA ================== --}}
  <div class="grid md:grid-cols-4 gap-4 mt-6">
    <div>
      <label class="block mb-1 font-medium text-gray-300">Código interno</label>
      <input type="text" value="{{ $code }}" readonly
             class="w-full bg-gray-200 text-gray-900 px-3 py-2 rounded font-mono">
      <input type="hidden" name="code" value="{{ $code }}">
    </div>
    <div>
      <label class="block mb-1 font-medium text-gray-300">N° Factura</label>
      <input type="text" name="invoice_number"
             class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700"
             placeholder="001-001-0001234">
    </div>
    <div>
      <label class="block mb-1 font-medium text-gray-300">Timbrado</label>
      <input type="text" name="timbrado"
             class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700"
             placeholder="Ej: 12345678">
    </div>
    <div>
      <label class="block mb-1 font-medium text-gray-300">Vencimiento timbrado</label>
      <input type="date" name="vencimiento_timbrado"
             class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700">
    </div>
  </div>

  {{-- ================== OTROS DATOS ================== --}}
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block mb-1 font-medium text-gray-300">Fecha de compra</label>
        <input
          type="date"
          name="purchased_at"
          value="{{ old('purchased_at', now()->format('Y-m-d')) }}"
          class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700"
          required
        >
      </div>

      <div>
        <label class="block mb-1 font-medium text-gray-300">Notas</label>
        <input
          type="text"
          name="notes"
          value="{{ old('notes') }}"
          class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700"
        >
      </div>
    </div>

  {{-- ================== ÍTEMS ================== --}}
<div class="space-y-3" x-data="productSearch()" x-init="init()">
  <h2 class="text-lg font-semibold text-gray-200">Ítems</h2>

  {{-- Buscador --}}
  <div class="relative">
    <input type="text"
           x-model="query"
           x-on:input.debounce.300ms="search()"
           x-on:keydown.escape="open=false"
           x-on:focus="if(results.length) open=true"
           placeholder="Buscar producto por código o nombre…"
           class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700"
           autocomplete="off">

    <div x-show="open"
         class="absolute z-20 mt-1 w-full max-h-60 overflow-auto rounded border border-gray-700 bg-white text-gray-900 shadow"
         @click.outside="open=false">
      <template x-if="results.length===0 && query.length > 1">
        <div class="px-3 py-2 text-sm text-gray-500">No se encontraron productos</div>
      </template>
      <template x-for="item in results" :key="item.id">
        <button type="button" class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm"
                @click="addItem(item)">
          <div class="font-medium" x-text="item.name"></div>
          <div class="text-xs text-gray-500"
               x-text="`Cod: ${item.code} • Stock: ${item.stock}`"></div>
        </button>
      </template>
    </div>
  </div>

  {{-- Botón manual para agregar fila (opcional) --}}
  <div>
    <button type="button"
            class="px-3 py-2 rounded bg-emerald-600 hover:bg-emerald-700"
            @click="addEmpty()">
      + Agregar ítem
    </button>
  </div>

  {{-- Tabla de ítems --}}
  <table class="w-full text-sm border border-gray-700 mt-3">
    <thead class="bg-gray-800 text-gray-300">
      <tr class="h-12">
        <th class="px-3 py-2 text-left">Producto</th>
        <th class="px-3 py-2 text-right">Cantidad</th>
        <th class="px-3 py-2 text-right">Costo</th>
        <th class="px-3 py-2 text-right">Subtotal</th>
        <th class="px-3 py-2"></th>
      </tr>
    </thead>
    <tbody>
      <template x-for="(it, i) in items" :key="i">
        <tr class="border-t border-gray-700 h-12">
          {{-- Producto --}}
          <td class="px-3 py-2">
            <div class="flex items-center h-10 gap-2">
              <span class="text-gray-100" x-text="it.name || '(seleccioná un producto)'"></span>
              <span class="text-xs text-gray-400" x-text="it.code ? `• ${it.code}` : ''"></span>
            </div>
          </td>

          {{-- Cantidad --}}
          <td class="px-3 py-2">
            <div class="flex items-center justify-end h-10">
              <input type="number" min="1" x-model.number="it.qty"
                     class="no-spinner h-10 leading-none w-24 text-sm text-right
                            bg-gray-700 text-white rounded px-2 box-border
                            outline-none border border-gray-600
                            focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>
          </td>

          {{-- Costo (visible formateado) --}}
          <td class="px-3 py-2">
            <div class="flex items-center justify-end h-10">
              <input type="text" inputmode="numeric" x-model="it.cost_display"
                     class="money-py h-10 leading-none w-28 text-sm text-right
                            bg-gray-700 text-white rounded px-2 box-border
                            outline-none border border-gray-600
                            focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>
          </td>

          {{-- Subtotal (formateado) --}}
          <td class="px-3 py-2">
            <div class="flex items-center justify-end h-10 leading-none text-right font-medium"
                 x-text="fmtPY((Number(it.qty||0) * cleanMoney(it.cost_display)))">
            </div>
          </td>

          {{-- Eliminar --}}
          <td class="px-3 py-2">
            <div class="flex items-center justify-center h-10">
              <button type="button" class="text-red-400 hover:text-red-300" @click="remove(i)">✖</button>
            </div>
          </td>

          {{-- Hidden inputs para el backend (NUMÉRICOS limpios) --}}
          <input type="hidden" :name="`items[${i}][product_id]`" :value="it.id">
          <input type="hidden" :name="`items[${i}][qty]`"        :value="it.qty">
          <input type="hidden" :name="`items[${i}][cost]`"       :value="cleanMoney(it.cost_display)">
        </tr>
      </template>
    </tbody>
  </table>

  {{-- Total --}}
  <div class="mt-3 text-right text-gray-200">
    <span class="mr-2">Total:</span>
    <span class="text-xl font-semibold" x-text="fmtPY(total)"></span>
  </div>

  {{-- Recalcular cuando cambian los ítems --}}
  <div x-effect="recalc()"></div>
</div>

{{-- Opcional: CSS para ocultar flechas del number --}}
<style>
  .no-spinner::-webkit-outer-spin-button,
  .no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
  .no-spinner { -moz-appearance: textfield; }
</style>


  {{-- ================== ACCIONES ================== --}}
  <div class="flex gap-2 pt-4">
    <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Guardar</button>
    <a href="{{ route('purchases.index') }}"
       class="px-4 py-2 border border-gray-500 text-gray-300 rounded hover:bg-gray-700">Cancelar</a>
  </div>
</form>

{{-- ================== SCRIPTS ================== --}}
<script>
  // Máscara visual para inputs de dinero (Gs. con puntos de miles)
  document.addEventListener('input', function(e){
    if(!e.target.matches('.money-py')) return;
    let raw = String(e.target.value || '').replace(/\D+/g,'');
    e.target.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  });

  // ===== Alpine helpers =====
  function supplierSearch() {
    return {
      query: '',
      results: [],
      open: false,
      loading: false,
      selected: null,
      search() {
        if (this.query.length < 2) { this.results=[]; return; }
        this.loading = true;
        fetch(`/api/suppliers?q=${encodeURIComponent(this.query)}`)
          .then(r => r.json())
          .then(data => { this.results = data; this.open = true; })
          .finally(() => this.loading=false);
      },
      select(item) {
        this.selected = item;
        this.query = `${item.name} (${item.ruc ?? '—'})`;
        this.open = false;
      }
    }
  }

  function productSearch() {
    return {
      query: '',
      results: [],
      open: false,
      items: [],
      // Helpers para money
      cleanMoney(s) { return parseInt(String(s ?? '').replace(/\D+/g,'')) || 0; },
      fmtPY(n) { return new Intl.NumberFormat('es-PY').format(Number(n||0)); },

      search() {
        if (this.query.length < 2) { this.results=[]; return; }
        fetch(`/api/products?q=${encodeURIComponent(this.query)}`)
          .then(r => r.json())
          .then(data => { this.results = data; this.open = true; });
      },
      addItem(item) {
        if (this.items.find(it => it.id === item.id)) {
          alert('⚠️ El producto ya está agregado.');
          return;
        }
        const base = Number(item.price_cash || 0);
        this.items.push({ ...item, qty: 1, cost_display: this.fmtPY(base) });
        this.query = ''; this.results=[]; this.open=false;
      },
      remove(i) { this.items.splice(i,1); }
    }
  }

  function purchaseForm() { return {}; }
</script>
@endsection
