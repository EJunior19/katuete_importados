{{-- resources/views/clients/index.blade.php --}}
@extends('layout.admin')

@section('content')
<div class="w-full px-6"> {{-- 🔹 Ahora usa todo el ancho disponible --}}
  <h1 class="text-3xl font-bold mb-6 text-green-400 flex items-center gap-2">
    👥 Lista de Clientes
  </h1>
  <div class="mb-4 flex justify-end">
  <a href="{{ route('clients.create') }}"
     class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">
     + Nuevo Cliente
  </a>
</div>


  @if(session('success'))
    <x-flash-message type="success" :message="session('success')" />
  @endif

  <div class="bg-gray-900 text-white rounded-xl shadow-2xl p-6 border-2 border-green-400">
    <div class="overflow-x-auto"> {{-- 🔹 Permite scroll horizontal si hay muchas columnas --}}
      <table class="w-full text-sm text-left">
        <thead class="bg-gray-800 text-green-300 text-base">
          <tr>
            <th class="px-6 py-3">ID</th>
            <th class="px-6 py-3">Código</th>
            <th class="px-6 py-3">Nombre</th>
            <th class="px-6 py-3">Email</th>
            <th class="px-6 py-3">Teléfono</th>
            <th class="px-6 py-3 text-center">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700 text-base">
          @foreach($clients as $c)
            <tr class="hover:bg-gray-800">
              <td class="px-6 py-3">{{ $c->id }}</td>
              <td class="px-6 py-3">{{ $c->code }}</td>
              <td class="px-6 py-3">{{ $c->name }}</td>
              <td class="px-6 py-3">{{ $c->email }}</td>
              <td class="px-6 py-3">{{ $c->phone ?? '—' }}</td>
              <td class="px-6 py-3">
                <div class="flex justify-center gap-2">
                  <x-action-buttons 
                    :show="route('clients.show',$c)" 
                    :edit="route('clients.edit',$c)" 
                    :delete="route('clients.destroy',$c)" 
                    :name="'el cliente '.$c->name" />
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
