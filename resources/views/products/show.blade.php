{{-- resources/views/products/show.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="w-full px-6">
  <h1 class="text-3xl font-bold mb-6 text-green-400 flex items-center gap-2">
    📦 Producto #{{ $product->id }}
  </h1>

  {{-- Flash global --}}
  <x-flash-message />

  <div class="bg-gray-900 text-white rounded-xl shadow-2xl p-8 border-2 border-green-400 w-full">
    {{-- Datos principales --}}
    <div class="grid md:grid-cols-2 gap-8 text-lg">
      <p>
        <span class="font-semibold text-green-300">Código:</span>
        <span class="font-mono text-xl">{{ $product->code ?? '—' }}</span>
      </p>

      <p>
        <span class="font-semibold text-green-300">Nombre:</span>
        <span class="text-xl">{{ $product->name }}</span>
      </p>

      <p>
        <span class="font-semibold text-green-300">Marca:</span>
        {{ $product->brand->name ?? '—' }}
      </p>

      <p>
        <span class="font-semibold text-green-300">Categoría:</span>
        {{ $product->category->name ?? '—' }}
      </p>

      <p>
        <span class="font-semibold text-green-300">Proveedor:</span>
        {{ $product->supplier->name ?? '—' }}
      </p>

      <p>
        <span class="font-semibold text-green-300">💵 Precio contado:</span>
        <span class="text-xl">
          {{ $product->price_cash !== null ? money_py($product->price_cash) : '—' }}
        </span>
      </p>

      <p class="flex items-center gap-2">
        <span class="font-semibold text-green-300">Stock:</span>
        <span class="px-3 py-1 rounded text-base font-bold
              {{ $product->stock > 0 ? 'bg-green-600' : 'bg-gray-600' }}">
          {{ $product->stock }}
        </span>
      </p>

      <p class="flex items-center gap-2">
        <span class="font-semibold text-green-300">Activo:</span>
        <x-table-row-status :active="$product->active" />
      </p>

      {{-- Notas (ocupa toda la fila) --}}
      <p class="md:col-span-2">
        <span class="font-semibold text-green-300">Notas:</span>
        {{ $product->notes ?? '—' }}
      </p>

      <p class="md:col-span-2 text-gray-400 text-base">
        📅 Creado: {{ $product->created_at?->format('d/m/Y H:i') }} ·
        🔄 Actualizado: {{ $product->updated_at?->format('d/m/Y H:i') }}
      </p>
    </div>

    {{-- Precio en cuotas --}}
    <h4 class="text-lg font-semibold text-green-400">Precios en cuotas</h4>

    @php
        $pis = $product->installments ?? collect();
    @endphp

    @if($pis->isEmpty())
      <p class="text-gray-400">—</p>
    @else
      <ul class="list-disc ml-6 text-gray-200">
        @foreach($pis as $pi)
          @php
              $n     = (int) $pi->installments;
              $cuota = (int) $pi->installment_price;
              $total = $n * $cuota;
          @endphp
          <li>
            {{ $n }} x @money($cuota)
            <span class="text-gray-400">(total: @money($total))</span>
          </li>
        @endforeach
      </ul>
    @endif

    {{-- Acciones --}}
    <div class="flex flex-wrap gap-4 mt-10">
      <x-action-buttons
        :edit="route('products.edit', $product)"
        :delete="route('products.destroy', $product)"
        :name="'el producto '.$product->name" />

      <a href="{{ route('products.index') }}"
         class="px-6 py-2 text-sm rounded-lg border border-gray-500 text-gray-300 hover:bg-gray-600 font-semibold shadow">
        ← Volver
      </a>
    </div>

    {{-- Últimos movimientos del producto --}}
    @if(isset($movements) && $movements->count())
      <div class="mt-12">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-2xl font-semibold text-green-300">📦 Últimos movimientos</h2>
          <a href="{{ route('inventory.create', ['product_id' => $product->id]) }}"
             class="px-4 py-2 text-sm rounded-lg border border-emerald-500 text-emerald-400 hover:bg-emerald-500/10 font-semibold shadow">
            ➕ Nuevo movimiento
          </a>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-700">
          <table class="min-w-full text-base text-left">
            <thead class="bg-gray-800 text-gray-200 uppercase text-xs tracking-wide">
              <tr>
                <th class="px-5 py-3">Fecha</th>
                <th class="px-5 py-3">Producto</th>
                <th class="px-5 py-3">Tipo</th>
                <th class="px-5 py-3">Cantidad</th>
                <th class="px-5 py-3">Razón</th>
                <th class="px-5 py-3">Usuario</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
              @foreach($movements as $m)
                <tr class="hover:bg-gray-800/60 transition">
                  <td class="px-5 py-3 font-mono text-gray-200">{{ $m->created_at?->format('Y-m-d H:i') }}</td>
                  <td class="px-5 py-3 text-white">{{ $m->product->name ?? '—' }}</td>
                  <td class="px-5 py-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                      {{ $m->type === 'entrada'
                        ? 'bg-emerald-600/30 text-emerald-300'
                        : 'bg-red-600/30 text-red-300' }}">
                      {{ ucfirst($m->type) }}
                    </span>
                  </td>
                  <td class="px-5 py-3 text-gray-300">{{ $m->quantity }}</td>
                  <td class="px-5 py-3 text-gray-400">{{ $m->reason ?? '—' }}</td>
                  <td class="px-5 py-3 text-gray-200">{{ $m->user->name ?? 'Sistema' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @else
      <p class="mt-10 text-gray-400 text-lg">No hay movimientos recientes para este producto.</p>
    @endif
  </div>
</div>
@endsection
