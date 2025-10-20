{{-- resources/views/categories/show.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="w-full px-6">
  <h1 class="text-3xl font-bold mb-6 text-green-400 flex items-center gap-2">
    📂 Categoría #{{ $category->id }}
  </h1>

  @if(session('success'))
    <x-flash-message type="success" :message="session('success')" />
  @endif

  <div class="bg-gray-900 text-white rounded-xl shadow-2xl p-10 border-2 border-green-400 w-full">
    <div class="grid md:grid-cols-2 gap-8 text-lg">
      <p>
        <span class="font-semibold text-green-300">Código:</span>
        <span class="font-mono">{{ $category->code ?? '—' }}</span>
      </p>

      <p>
        <span class="font-semibold text-green-300">Nombre:</span>
        {{ $category->name }}
      </p>

      <p>
        <span class="font-semibold text-green-300">Activo:</span>
        <x-table-row-status :active="$category->active" />
      </p>

      <p>
        <span class="font-semibold text-green-300">Productos asociados:</span>
        {{ $category->products_count ?? 0 }}
      </p>

      <p class="md:col-span-2 text-gray-400 text-sm">
        📅 Creado: {{ $category->created_at?->format('d/m/Y H:i') }} ·
        🔄 Actualizado: {{ $category->updated_at?->format('d/m/Y H:i') }}
      </p>
    </div>

    {{-- Últimos productos de la categoría --}}
    <div class="mt-10">
      <h2 class="text-xl font-semibold text-green-300 mb-4">Últimos productos</h2>

      @if(isset($latestProducts) && $latestProducts->count())
        <div class="overflow-x-auto rounded-lg border border-gray-700">
          <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-800 text-gray-200 uppercase text-xs tracking-wide">
              <tr>
                <th class="px-4 py-2">ID</th>
                <th class="px-4 py-2">Código</th>
                <th class="px-4 py-2">Nombre</th>
                <th class="px-4 py-2">Activo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
              @foreach($latestProducts as $p)
                <tr class="hover:bg-gray-800/60">
                  <td class="px-4 py-2">{{ $p->id }}</td>
                  <td class="px-4 py-2 font-mono">{{ $p->code ?? '—' }}</td>
                  <td class="px-4 py-2">{{ $p->name }}</td>
                  <td class="px-4 py-2"><x-table-row-status :active="$p->active" /></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <p class="text-gray-400">No hay productos recientes en esta categoría.</p>
      @endif
    </div>

    {{-- Acciones --}}
    <div class="flex flex-wrap gap-4 mt-10">
      <x-action-buttons
        :edit="route('categories.edit', $category)"
        :delete="route('categories.destroy', $category)"
        :name="'la categoría '.$category->name" />

      {{-- Activar/Desactivar categoría --}}
      <form method="POST" action="{{ route('categories.toggle', $category) }}">
        @csrf
        @method('PUT')
        <button class="px-6 py-2 text-sm rounded-lg border border-purple-500 text-purple-400 hover:bg-purple-500 hover:text-white font-semibold shadow">
          {{ $category->active ? 'Desactivar' : 'Activar' }}
        </button>
      </form>

      <a href="{{ route('categories.index') }}"
         class="px-6 py-2 text-sm rounded-lg border border-gray-500 text-gray-300 hover:bg-gray-600 font-semibold shadow">
         ← Volver
      </a>
    </div>
  </div>
</div>
@endsection
