{{-- resources/views/products/index.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-gray-200">📦 Productos</h1>

  {{-- Botón para crear nuevo producto --}}
  <x-create-button route="{{ route('products.create') }}" text="Nuevo producto" />
</div>

{{-- Mensajes flash --}}
<x-flash-message />

<div class="bg-gray-900 text-white rounded-xl shadow-md border border-gray-700">
  <div class="overflow-x-auto rounded-t-xl">
    <table class="min-w-full text-sm text-left">
      <thead class="bg-gray-700 text-gray-200 uppercase text-xs tracking-wide">
        <tr>
          <th class="px-4 py-3">#</th>
          <th class="px-4 py-3">Código</th>
          <th class="px-4 py-3">Nombre</th>
          <th class="px-4 py-3">Marca</th>
          <th class="px-4 py-3">Categoría</th>
          <th class="px-4 py-3">Proveedor</th>
          <th class="px-4 py-3 text-right">Precio</th>
          <th class="px-4 py-3 text-right">Stock</th>
          <th class="px-4 py-3 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-700">
        @forelse($products as $p)
          <tr class="hover:bg-gray-800/60 transition">
            <td class="px-4 py-3 font-medium">{{ $p->id }}</td>
            <td class="px-4 py-3 font-mono">{{ $p->code ?? '—' }}</td>
            <td class="px-4 py-3">{{ $p->name }}</td>
            <td class="px-4 py-3">{{ $p->brand->name ?? '—' }}</td>
            <td class="px-4 py-3">{{ $p->category->name ?? '—' }}</td>
            <td class="px-4 py-3">{{ $p->supplier->name ?? '—' }}</td>
            <td class="px-4 py-3 text-right">
              @if(!is_null($p->price_cash))
                @money($p->price_cash)
              @else
                —
              @endif
            </td>
            <td class="px-4 py-3 text-right">
              <x-table-row-status :active="$p->stock > 0" :label="$p->stock" />
            </td>
            <td class="px-4 py-3">
              <x-action-buttons 
                :show="route('products.show',$p)"
                :edit="route('products.edit',$p)"
                :delete="route('products.destroy',$p)"
                :name="'el producto '.$p->name" />
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="px-6 py-8 text-center text-gray-400 italic">Sin productos</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Paginación --}}
  <div class="p-4 border-t border-gray-700">
    {{ $products->links() }}
  </div>
</div>
@endsection
