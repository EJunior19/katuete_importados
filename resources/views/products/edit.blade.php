@extends('layout.admin')

@section('content')
<h1 class="text-2xl font-semibold text-black-200 mb-4">✏ Editar producto</h1>

{{-- 🔹 Errores de validación --}}
@if ($errors->any())
  <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
    <ul class="list-disc ml-5">
      @foreach ($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="{{ route('products.update',$product) }}"
      class="bg-gray-900 text-white rounded shadow p-4 space-y-4">
  @csrf @method('PUT')

  {{-- Código (solo lectura) --}}
  <div>
    <label class="block mb-1 font-medium">Código</label>
    <input type="text" value="{{ $product->code ?? '—' }}" disabled
           class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 text-gray-300 font-mono">
  </div>

  {{-- Nombre --}}
  <div>
    <label class="block mb-1 font-medium">Nombre</label>
    <input type="text" name="name" value="{{ old('name',$product->name) }}" required
           class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700 focus:ring focus:ring-blue-500">
  </div>

  {{-- Marca --}}
  <div>
    <label class="block mb-1 font-medium">Marca</label>
    <select name="brand_id" required
            class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700 focus:ring focus:ring-blue-500">
      @foreach($brands as $b)
        <option value="{{ $b->id }}" @selected(old('brand_id',$product->brand_id)==$b->id)>
          {{ $b->name }}
        </option>
      @endforeach
    </select>
  </div>

  {{-- Categoría --}}
  <div>
    <label class="block mb-1 font-medium">Categoría</label>
    <select name="category_id" required
            class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700 focus:ring focus:ring-blue-500">
      @foreach($categories as $c)
        <option value="{{ $c->id }}" @selected(old('category_id',$product->category_id)==$c->id)>
          {{ $c->name }}
        </option>
      @endforeach
    </select>
  </div>

  {{-- Proveedor --}}
  <div>
    <label class="block mb-1 font-medium">Proveedor</label>
    <select name="supplier_id" required
            class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700 focus:ring focus:ring-blue-500">
      @foreach($suppliers as $s)
        <option value="{{ $s->id }}" @selected(old('supplier_id',$product->supplier_id)==$s->id)>
          {{ $s->name }}
        </option>
      @endforeach
    </select>
  </div>

  {{-- Precio contado (con máscara visual) --}}
  <div>
    <label class="block mb-1 font-medium">Precio contado (Gs.)</label>
    <input type="text" inputmode="numeric" name="price_cash"
           value="{{ old('price_cash', money_py($product->price_cash, false)) }}"
           class="money-py w-full px-3 py-2 rounded bg-gray-800 border border-gray-700"
           placeholder="1.500.000">
  </div>

  {{-- Precios en cuotas dinámicos --}}
  <div>
    <label class="block mb-1 font-medium">Precios en cuotas (opcional)</label>

    <div id="installments-wrapper" class="space-y-2">
      @php
        $pis = $product->installments ?? collect();
      @endphp

      {{-- Si hay datos viejos por validación --}}
      @if(old('installments'))
        @foreach(old('installments') as $i => $cuota)
          <div class="flex gap-2 installment-row">
            <input type="number" min="1" name="installments[{{ $i }}]"
                   value="{{ old('installments.'.$i) }}"
                   placeholder="N° de cuotas"
                   class="w-1/3 px-3 py-2 rounded bg-gray-800 border border-gray-700">
            <input type="text" inputmode="numeric" name="installment_prices[{{ $i }}]"
                   value="{{ old('installment_prices.'.$i) }}"
                   placeholder="Precio por cuota"
                   class="money-py w-2/3 px-3 py-2 rounded bg-gray-800 border border-gray-700">
            <button type="button" class="remove-installment px-2 bg-red-600 text-white rounded">✖</button>
          </div>
        @endforeach

      {{-- Si el producto ya tiene cuotas cargadas --}}
      @elseif($pis->count())
        @foreach($pis as $idx => $inst)
          <div class="flex gap-2 installment-row">
            <input type="number" min="1" name="installments[{{ $idx }}]"
                   value="{{ old('installments.'.$idx, $inst->installments) }}"
                   placeholder="N° de cuotas"
                   class="w-1/3 px-3 py-2 rounded bg-gray-800 border border-gray-700">
            <input type="text" inputmode="numeric" name="installment_prices[{{ $idx }}]"
                   value="{{ old('installment_prices.'.$idx, money_py((int)$inst->installment_price, false)) }}"
                   placeholder="Precio por cuota"
                   class="money-py w-2/3 px-3 py-2 rounded bg-gray-800 border border-gray-700">
            <button type="button" class="remove-installment px-2 bg-red-600 text-white rounded">✖</button>
          </div>
        @endforeach

      {{-- Sin cuotas aún: una fila vacía inicial --}}
      @else
        <div class="flex gap-2 installment-row">
          <input type="number" min="1" name="installments[0]"
                 placeholder="N° de cuotas"
                 class="w-1/3 px-3 py-2 rounded bg-gray-800 border border-gray-700">
          <input type="text" inputmode="numeric" name="installment_prices[0]"
                 placeholder="Precio por cuota"
                 class="money-py w-2/3 px-3 py-2 rounded bg-gray-800 border border-gray-700">
          <button type="button" class="remove-installment px-2 bg-red-600 text-white rounded">✖</button>
        </div>
      @endif
    </div>

    <button type="button" id="add-installment"
            class="mt-2 px-3 py-1 bg-purple-600 text-white rounded hover:bg-purple-700">
      ➕ Agregar cuota
    </button>
  </div>

  {{-- Stock (solo lectura) --}}
  <div>
    <label class="block mb-1 font-medium">Stock</label>
    <div class="px-3 py-2 rounded bg-gray-800 border border-gray-700 inline-flex items-center gap-2">
      <span class="px-2 py-0.5 rounded text-xs font-semibold
                  {{ $product->stock > 0 ? 'bg-green-600 text-white' : 'bg-gray-500 text-gray-100' }}">
        {{ $product->stock }}
      </span>
      <span class="text-xs text-gray-400">(se actualiza con Compras/Ventas)</span>
    </div>
  </div>

  {{-- Activo --}}
  <div class="flex items-center">
    <input type="hidden" name="active" value="0">
    <input type="checkbox" name="active" id="active" value="1"
           class="w-4 h-4 text-blue-600 border-gray-600 rounded focus:ring-blue-500"
           {{ old('active', $product->active) ? 'checked' : '' }}>
    <label for="active" class="ml-2">Activo</label>
  </div>

  {{-- Notas --}}
  <div>
    <label class="block mb-1 font-medium">Notas</label>
    <textarea name="notes" rows="3"
              class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700">{{ old('notes',$product->notes) }}</textarea>
  </div>

  {{-- Acciones --}}
  <div class="flex gap-2 mt-4">
    <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Guardar</button>
    <a href="{{ route('products.index',$product) }}"
       class="px-4 py-2 border border-gray-400 text-gray-400 rounded hover:bg-gray-500 hover:text-white">
       Cancelar
    </a>
  </div>
</form>

{{-- 🔹 Script JS para cuotas dinámicas y máscara de miles --}}
@push('scripts')
<script>
  // Máscara visual de miles (no afecta al backend; el middleware lo limpia)
  document.addEventListener('input', function(e){
    if(!e.target.matches('.money-py')) return;
    let raw = e.target.value.replace(/\D+/g,'');
    e.target.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  });

  // Agregar filas dinámicas con índices consistentes
  document.getElementById('add-installment').addEventListener('click', function() {
    const wrapper = document.getElementById('installments-wrapper');
    const idx = wrapper.querySelectorAll('.installment-row').length;

    const div = document.createElement('div');
    div.className = 'flex gap-2 installment-row';
    div.innerHTML = `
      <input type="number" min="1" name="installments[${idx}]"
             placeholder="N° de cuotas"
             class="w-1/3 px-3 py-2 rounded bg-gray-800 border border-gray-700">
      <input type="text" inputmode="numeric" name="installment_prices[${idx}]"
             placeholder="Precio por cuota"
             class="money-py w-2/3 px-3 py-2 rounded bg-gray-800 border border-gray-700">
      <button type="button" class="remove-installment px-2 bg-red-600 text-white rounded">✖</button>
    `;
    wrapper.appendChild(div);
  });

  // Remover fila
  document.getElementById('installments-wrapper').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-installment')) {
      e.target.closest('.installment-row').remove();
    }
  });
</script>
@endpush

@endsection
