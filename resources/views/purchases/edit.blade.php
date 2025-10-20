{{-- resources/views/purchases/edit.blade.php --}}
@extends('layout.admin')

@section('content')
<h1 class="text-2xl font-semibold text-blue-400 mb-4">
  ✏️ Editar compra #{{ $purchase->id }}
</h1>

{{-- ⚠️ Errores --}}
@if ($errors->any())
  <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
    <ul class="list-disc ml-5">
      @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="{{ route('purchases.update',$purchase) }}"
      class="bg-gray-900 text-white rounded shadow p-6 space-y-6">
  @csrf
  @method('PUT')

  {{-- ================= CABECERA ================= --}}
  <div class="grid md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm text-gray-300 mb-1">ID</label>
      <input type="text" value="{{ $purchase->id }}" readonly
             class="w-full bg-gray-800 border border-gray-700 text-gray-400 px-3 py-2 rounded">
    </div>
    <div>
      <label class="block text-sm text-gray-300 mb-1">Código</label>
      <input type="text" value="{{ $purchase->code }}" readonly
             class="w-full bg-gray-800 border border-gray-700 text-blue-400 px-3 py-2 rounded font-mono font-semibold">
    </div>
  </div>

  {{-- ================= PROVEEDOR Y DATOS ================= --}}
  <div class="grid md:grid-cols-4 gap-4">
    <div>
      <label class="block text-sm text-gray-300 mb-1">Proveedor</label>
      <select name="supplier_id" required
              class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700">
        @foreach($suppliers as $s)
          <option value="{{ $s->id }}"
            {{ (string)old('supplier_id',$purchase->supplier_id) === (string)$s->id ? 'selected' : '' }}>
            {{ $s->name }}
          </option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm text-gray-300 mb-1">Fecha de compra</label>
      {{-- Requiere $casts['purchased_at' => 'datetime'] en el modelo --}}
      <input type="date" name="purchased_at"
             value="{{ old('purchased_at', $purchase->purchased_at?->format('Y-m-d')) }}"
             class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700" required>
    </div>

    <div>
      <label class="block text-sm text-gray-300 mb-1">Notas</label>
      <input type="text" name="notes" value="{{ old('notes',$purchase->notes) }}"
             class="w-full px-3 py-2 rounded bg-gray-800 border border-gray-700">
    </div>

    <div>
      <label class="block text-sm text-gray-300 mb-1">Estado</label>
      <x-status-select name="estado" :value="old('estado',$purchase->estado)" class="w-full"/>
    </div>
  </div>

  {{-- ================= ÍTEMS ================= --}}
  <div>
    <div class="flex items-center justify-between mb-2">
      <h2 class="text-lg font-semibold text-gray-200">Ítems de la compra</h2>
      <span class="text-sm text-gray-400">Solo lectura</span>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm border border-gray-700 rounded">
        <thead class="bg-gray-800 text-gray-300 uppercase">
          <tr>
            <th class="px-3 py-2">Código</th>
            <th class="px-3 py-2">Producto</th>
            <th class="px-3 py-2 text-right">Cant.</th>
            <th class="px-3 py-2 text-right">Costo</th>
            <th class="px-3 py-2 text-right">Subtotal</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700 text-gray-200">
          @forelse($purchase->items as $it)
            @php
              $qty      = (int)($it->qty ?? 0);
              $cost     = (int)($it->cost ?? 0);
              $subtotal = $qty * $cost;
            @endphp
            <tr>
              <td class="px-3 py-2 font-mono text-blue-300">{{ $it->product?->code ?? '—' }}</td>
              <td class="px-3 py-2">{{ $it->product?->name ?? '—' }}</td>
              <td class="px-3 py-2 text-right">{{ number_format($qty, 0, ',', '.') }}</td>
              <td class="px-3 py-2 text-right">@money($cost)</td>
              <td class="px-3 py-2 text-right font-semibold text-emerald-400">
                @money($subtotal)
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-3 py-4 text-center text-gray-400">Sin ítems</td>
            </tr>
          @endforelse

          
        </tbody>
        <tfoot class="bg-gray-800 text-gray-200">
          <tr>
            <th colspan="4" class="px-3 py-2 text-right">Total</th>
            <th class="px-3 py-2 text-right text-lg font-bold text-emerald-400">
              @money($total ?? 0)
            </th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  {{-- ================= ACCIONES ================= --}}
  <div class="flex gap-3 pt-4">
    <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">
      💾 Actualizar
    </button>
    <a href="{{ route('purchases.index') }}"
       class="px-4 py-2 border border-gray-500 text-gray-300 rounded hover:bg-gray-700">
       Cancelar
    </a>
  </div>
</form>
@endsection
