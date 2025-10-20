{{-- resources/views/categories/index.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-black-200">📂 Categorías</h1>

  {{-- Botón para crear nueva categoría --}}
  <x-create-button route="{{ route('categories.create') }}" text="Nueva categoría" />
</div>

{{-- Mensajes flash --}}
<x-flash-message />

<div class="bg-gray-800 text-white rounded-xl shadow-md border border-gray-700">
  <div class="overflow-x-auto rounded-t-xl">
    <table class="min-w-full text-sm text-left">
      <thead class="bg-gray-700 text-gray-200 uppercase text-xs tracking-wide">
        <tr>
          <th class="px-6 py-3">Id</th>
          <th class="px-6 py-3">Código</th>
          <th class="px-6 py-3">Nombre</th>
          <th class="px-6 py-3">Activo</th>
          <th class="px-6 py-3 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-700">
        @forelse($categories as $c)
          <tr class="hover:bg-gray-700/60 transition">
            <td class="px-6 py-3 font-medium">{{ $c->id }}</td>
            <td class="px-6 py-3 font-mono">{{ $c->code }}</td>
            <td class="px-6 py-3">{{ $c->name }}</td>
            <td class="px-6 py-3">
              <x-table-row-status :active="$c->active" />
            </td>
            <td class="px-6 py-3">
              <x-action-buttons 
                :show="route('categories.show',$c)"
                :edit="route('categories.edit',$c)"
                :delete="route('categories.destroy',$c)"
                :name="'la categoría '.$c->name" />
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">Sin categorías</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Paginación --}}
  <div class="p-4 border-t border-gray-700">
    {{ $categories->links() }}
  </div>
</div>
@endsection
