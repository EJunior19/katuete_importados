{{-- resources/views/purchase_orders/edit.blade.php --}}
@extends('layout.admin')

@section('content')
@php
    // Preparamos los datos para Alpine de forma segura (evita paréntesis / corchetes desbalanceados en x-data)
    $initialItems = $order->items->map(function ($it) {
        return [
            'id'           => $it->id,
            'product_id'   => $it->product_id,
            'product_name' => optional($it->product)->name,
            'quantity'     => (int) $it->quantity,
            'unit_price'   => (float) $it->unit_price,
        ];
    })->values();

    $productsList = $products->map(function ($p) {
        return ['id' => $p->id, 'name' => $p->name, 'code' => $p->code];
    })->values();
@endphp

<div class="w-full px-6 text-gray-200"
     x-data='poEditor({ initialItems: @json($initialItems), products: @json($productsList) })'>

  {{-- Header + volver --}}
  <div class="flex items-center justify-between mb-6">
    <div>
      <a href="{{ route('purchase_orders.index') }}"
         class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-700 text-gray-300 hover:bg-gray-800">
        ← Volver a Órdenes
      </a>
      <h1 class="mt-3 text-3xl font-bold text-green-400">📝 Editar OC {{ $order->order_number }}</h1>
      <p class="text-gray-400 text-sm mt-1">
        Creado: {{ $order->created_at?->format('d/m/Y H:i') }} ·
        Actualizado: {{ $order->updated_at?->format('d/m/Y H:i') }}
      </p>
    </div>
    <div class="text-right">
      <div class="text-sm text-gray-400">Total actual</div>
      <div class="text-2xl font-bold text-green-300">₲ <span x-text="formatMoney(grandTotal())"></span></div>
    </div>
  </div>

  {{-- Errores / flashes --}}
  @if ($errors->any())
    <div class="bg-red-900/60 border border-red-500 text-red-300 rounded-xl px-4 py-3 mb-4">
      <ul class="list-disc list-inside text-sm">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif
  <x-flash-message />

  <form method="POST" action="{{ route('purchase_orders.update', $order) }}">
    @csrf
    @method('PUT')

    {{-- Cabecera --}}
    <div class="bg-gray-900 border border-gray-700 rounded-xl p-6 mb-8">
      <div class="grid md:grid-cols-3 gap-6">

        <div>
          <label class="block text-sm font-semibold text-green-300 mb-1">Proveedor</label>
          <select name="supplier_id" required
                  class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2">
            <option value="">Seleccione…</option>
            @foreach($suppliers as $s)
              <option value="{{ $s->id }}" @selected(old('supplier_id', $order->supplier_id) == $s->id)>
                {{ $s->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-sm font-semibold text-green-300 mb-1">Fecha de orden</label>
          <input type="date" name="order_date" required
                 value="{{ old('order_date', \Illuminate\Support\Carbon::parse($order->order_date)->toDateString()) }}"
                 class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2">
        </div>

        <div>
          <label class="block text-sm font-semibold text-green-300 mb-1">Entrega estimada</label>
          <input type="date" name="expected_date"
                 value="{{ old('expected_date', optional($order->expected_date)->toDateString()) }}"
                 class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2">
        </div>

        <div class="md:col-span-3">
          <label class="block text-sm font-semibold text-green-300 mb-1">Notas</label>
          <textarea name="notes" rows="3"
                    class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2">{{ old('notes', $order->notes) }}</textarea>
        </div>

        <div>
          <label class="block text-sm font-semibold text-green-300 mb-1">Estado</label>
          @php $st = old('status', $order->status); @endphp
          <select name="status" class="w-full rounded-lg border-gray-700 bg-gray-800 text-gray-100 px-3 py-2">
            @foreach(['borrador','enviado','recibido','cerrado'] as $opt)
              <option value="{{ $opt }}" @selected($st === $opt)>{{ ucfirst($opt) }}</option>
            @endforeach
          </select>
        </div>

      </div>
    </div>

    {{-- Ítems --}}
    <div class="bg-gray-900 border border-gray-700 rounded-xl overflow-hidden">
      <div class="flex items-center justify-between p-4">
        <h2 class="font-semibold text-green-300">🧩 Ítems</h2>
        <div class="flex items-center gap-3 text-sm text-gray-300">
          <button type="button" @click="addItem()"
                  class="px-3 py-1.5 rounded bg-sky-700 hover:bg-sky-800 font-semibold">
            ➕ Agregar ítem
          </button>
        </div>
      </div>

      <div class="overflow-x-auto border-t border-gray-700">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-800 text-gray-300 uppercase text-xs">
            <tr>
              <th class="text-left px-3 py-2">Producto</th>
              <th class="text-right px-3 py-2">Cantidad</th>
              <th class="text-right px-3 py-2">P. unit.</th>
              <th class="text-right px-3 py-2">Subtotal</th>
              <th class="px-3 py-2"></th>
            </tr>
          </thead>

        <tbody class="divide-y divide-gray-700" x-ref="tbody">
          <template x-for="(row, idx) in items" :key="row.__key">
            <tr class="hover:bg-gray-800/60">
              {{-- id oculto si existe (debe ir dentro de una celda para HTML válido) --}}
              <td class="hidden">
                <input type="hidden" :name="`items[${idx}][id]`" :value="row.id ?? ''">
              </td>

              <td class="px-3 py-2 min-w-[280px]">
                <select class="w-full rounded border-gray-700 bg-gray-800 text-gray-100 px-2 py-1.5"
                        :name="`items[${idx}][product_id]`"
                        x-model.number="row.product_id">
                  <option value="">Seleccione…</option>
                  <template x-for="p in products" :key="p.id">
                    <option :value="p.id" x-text="p.name + (p.code ? ' — ' + p.code : '')"></option>
                  </template>
                </select>
              </td>

              <td class="px-3 py-2 text-right">
                <input type="number" min="1" step="1"
                       class="w-28 text-right rounded border-gray-700 bg-gray-800 text-gray-100 px-2 py-1.5"
                       :name="`items[${idx}][quantity]`"
                       x-model.number="row.quantity">
              </td>

              <td class="px-3 py-2 text-right">
                <input type="number" min="0" step="0.01"
                       class="w-32 text-right rounded border-gray-700 bg-gray-800 text-gray-100 px-2 py-1.5"
                       :name="`items[${idx}][unit_price]`"
                       x-model.number="row.unit_price">
              </td>

              <td class="px-3 py-2 text-right font-semibold">
                ₲ <span x-text="formatMoney(subtotal(row))"></span>
              </td>

              <td class="px-3 py-2 text-right">
                <button type="button" @click="removeItem(idx)"
                        class="px-3 py-1 rounded bg-rose-700 hover:bg-rose-800 text-white">
                  Eliminar
                </button>
              </td>
            </tr>
          </template>
        </tbody>

          <tfoot class="bg-gray-800">
            <tr>
              <td colspan="3" class="px-3 py-3 text-right font-semibold">Total</td>
              <td class="px-3 py-3 text-right text-green-300 font-bold">₲ <span x-text="formatMoney(grandTotal())"></span></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    {{-- Acciones --}}
    <div class="mt-8 flex flex-wrap gap-3">
      <a href="{{ route('purchase_orders.index') }}"
         class="px-6 py-2 rounded-lg border border-gray-600 text-gray-300 hover:bg-gray-700 font-semibold shadow">
        ← Volver
      </a>
      <button class="px-6 py-2 rounded-lg bg-green-600 hover:bg-green-700 font-semibold shadow">
        Guardar cambios
      </button>
    </div>
  </form>
</div>

{{-- Alpine helpers --}}
<script>
function poEditor({ initialItems = [], products = [] }) {
  // Si no hay items, agrega una fila vacía por UX
  const seed = (initialItems && initialItems.length) ? initialItems : [
    { id: null, product_id: '', quantity: 1, unit_price: 0, __key: (crypto.randomUUID && crypto.randomUUID()) || Math.random() }
  ];

  // Normaliza con __key para el x-for
  const normalize = (arr) => arr.map(it => ({
    id:         (it && 'id' in it) ? it.id : null,
    product_id: (it && 'product_id' in it) ? it.product_id : '',
    quantity:   (it && 'quantity' in it) ? it.quantity : 1,
    unit_price: (it && 'unit_price' in it) ? it.unit_price : 0,
    __key:      (crypto.randomUUID && crypto.randomUUID()) || Math.random()
  }));

  return {
    products,
    items: normalize(seed),

    addItem() {
      this.items.push({
        id: null, product_id: '', quantity: 1, unit_price: 0,
        __key: (crypto.randomUUID && crypto.randomUUID()) || Math.random()
      });
      this.$nextTick(() => {});
    },

    removeItem(idx) {
      this.items.splice(idx, 1);
      if (this.items.length === 0) this.addItem();
    },

    subtotal(row) {
      const q = Number(row?.quantity || 0);
      const u = Number(row?.unit_price || 0);
      return q * u;
    },

    grandTotal() {
      return this.items.reduce((acc, r) => acc + this.subtotal(r), 0);
    },

    formatMoney(n) {
      try {
        return new Intl.NumberFormat('es-PY', { maximumFractionDigits: 0 }).format(n || 0);
      } catch (e) {
        return (n || 0).toFixed(0);
      }
    }
  }
}
</script>
@endsection
