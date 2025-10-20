{{-- resources/views/brands/index.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-3xl font-bold text-green-400 flex items-center gap-2">
    🏷️ Marcas
  </h1>

  {{-- Botón para crear nueva marca --}}
  <x-create-button route="{{ route('brands.create') }}" text="Nueva marca" />
</div>

{{-- Mensajes flash --}}
<x-flash-message />

<div class="bg-gray-900 text-white rounded-xl shadow-md border-2 border-gray-800">
  <div class="overflow-x-auto rounded-t-xl">
    <table class="w-full text-left text-base">
      <thead class="bg-gray-800 text-gray-200 uppercase text-xs tracking-wide">
        <tr>
          <th class="px-6 py-3">ID</th>
          <th class="px-6 py-3">Código</th>
          <th class="px-6 py-3">Nombre</th>
          <th class="px-6 py-3">Productos</th>
          <th class="px-6 py-3">Activo</th>
          <th class="px-6 py-3 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-800">
        @forelse($brands as $b)
          <tr class="hover:bg-gray-800/60 transition">
            <td class="px-6 py-3 font-medium">{{ $b->id }}</td>
            <td class="px-6 py-3 font-mono">{{ $b->code ?? '—' }}</td>
            <td class="px-6 py-3">{{ $b->name }}</td>
            <td class="px-6 py-3">
              {{ $b->products_count ?? ($b->products->count() ?? 0) }}
            </td>
            <td class="px-6 py-3">
              <x-table-row-status :active="$b->active" />
            </td>
            <td class="px-6 py-3">
              <x-action-buttons 
                :show="route('brands.show',$b)"
                :edit="route('brands.edit',$b)"
                :delete="route('brands.destroy',$b)"
                :name="'la marca '.$b->name" />
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center text-gray-400 px-6 py-8 italic">
              No hay marcas registradas.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Paginación --}}
  <div class="p-4 border-t border-gray-800">
    {{ $brands->links() }}
  </div>
</div>
@endsection
