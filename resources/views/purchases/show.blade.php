{{-- resources/views/purchases/show.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-3xl font-bold text-emerald-400">🛒 Compra #{{ $purchase->code }}</h1>
  <a href="{{ route('purchases.index') }}" 
     class="px-4 py-2 rounded-lg bg-gray-800 text-white hover:bg-gray-700 transition">
    ← Volver
  </a>
</div>

@if(session('success'))
  <div class="mb-4 p-3 bg-green-100 text-green-800 border border-green-300 rounded text-sm">
    {{ session('success') }}
  </div>
@endif

<div class="grid lg:grid-cols-2 gap-6">
  {{-- 📌 Información --}}
  <div class="rounded-2xl border border-emerald-600 bg-gray-800 p-6 shadow-lg">
    <h2 class="text-lg font-semibold text-emerald-300 mb-3">📋 Información</h2>
    <dl class="grid grid-cols-2 gap-y-3 text-white">
      <dt class="text-gray-300">ID</dt>
      <dd class="font-medium">{{ $purchase->id }}</dd>

      <dt class="text-gray-300">Nmr Factura</dt>
      <dd class="font-mono">{{ $purchase->invoice_number }}</dd>

      <dt class="text-gray-300">Proveedor</dt>
      <dd>{{ $purchase->supplier?->name ?? '—' }}</dd>

      <dt class="text-gray-300">Fecha</dt>
      <dd>{{ $purchase->purchased_at?->format('d/m/Y H:i') ?? '—' }}</dd>

      <dt class="text-gray-300">Notas</dt>
      <dd class="italic">{{ $purchase->notes ?? '—' }}</dd>

      {{-- Estado con form --}}
      <dt class="text-gray-300">Estado</dt>
      <dd>
        <form method="POST" action="{{ route('purchases.updateStatus',$purchase) }}" class="flex items-center gap-2">
          @csrf @method('PUT')
          <select name="estado" class="bg-gray-700 text-white rounded px-2 py-1">
            <option value="pendiente" {{ $purchase->estado === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="aprobado" {{ $purchase->estado === 'aprobado' ? 'selected' : '' }}>Aprobado</option>
            <option value="rechazado" {{ $purchase->estado === 'rechazado' ? 'selected' : '' }}>Rechazado</option>
          </select>
          <button class="px-3 py-1 bg-emerald-600 text-white rounded hover:bg-emerald-700">
            Guardar
          </button>
        </form>
      </dd>
    </dl>
  </div>

  {{-- 💰 Totales (usar el valor guardado) --}}
  <div class="rounded-2xl border border-indigo-600 bg-gray-800 p-6 shadow-lg">
    <h2 class="text-lg font-semibold text-indigo-300 mb-3">💰 Totales</h2>
    <div class="space-y-1 text-white">
      <div class="flex justify-between text-xl font-bold text-emerald-400">
        <span>Total:</span>
        <span>@money($purchase->total_amount ?? 0)</span>
      </div>
    </div>
  </div>
</div>

{{-- 📦 Ítems --}}
<div class="mt-8 rounded-2xl border border-gray-700 bg-gray-900 p-6 shadow-lg">
  <h2 class="text-xl font-semibold text-emerald-400 mb-4">📦 Productos de la compra</h2>
  <div class="overflow-x-auto rounded-lg border border-gray-700">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left text-gray-200 bg-gray-800">
          <th class="px-4 py-2">Código</th>
          <th class="px-4 py-2">Nombre</th>
          <th class="px-4 py-2 text-right">Cantidad</th>
          <th class="px-4 py-2 text-right">Costo</th>
          <th class="px-4 py-2 text-right">Subtotal</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-zinc-800 text-zinc-200">
        @forelse($purchase->items as $it)
          @php
            $qty = (int)($it->qty ?? 0);
            $cost = (int)($it->cost ?? 0);
            $subtotal = $qty * $cost;
          @endphp
          <tr>
            <td class="px-4 py-2 font-mono">{{ $it->product?->code ?? '—' }}</td>
            <td class="px-4 py-2">{{ $it->product?->name ?? '—' }}</td>
            <td class="px-4 py-2 text-right">{{ number_format($qty, 0, ',', '.') }}</td>
            <td class="px-4 py-2 text-right">@money($cost)</td>
            <td class="px-4 py-2 text-right">@money($subtotal)</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="px-4 py-6 text-center text-gray-400">Sin ítems</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ⚙️ Acciones --}}
<div class="mt-6 flex gap-2">
  <a href="{{ route('purchases.edit',$purchase) }}"
     class="px-4 py-2 rounded bg-yellow-600 hover:bg-yellow-700 text-white">✏️ Editar</a>

  <form method="POST" action="{{ route('purchases.destroy',$purchase) }}"
        onsubmit="return confirm('¿Eliminar compra {{ $purchase->code }}?')">
    @csrf @method('DELETE')
    <button class="px-4 py-2 rounded bg-red-600 hover:bg-red-700 text-white">🗑️ Eliminar</button>
  </form>
</div>
@endsection
