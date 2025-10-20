@extends('layout.admin')

@section('content')
<div class="flex justify-between items-center mb-4">
    <h3 class="text-xl font-semibold text-gray-700">✏ Editar Contacto de {{ $client->name }}</h3>
    <a href="{{ route('clients.show', $client) }}" 
       class="px-3 py-1.5 text-sm border rounded bg-gray-100 text-gray-600 hover:bg-gray-200">
       ⬅ Volver
    </a>
</div>

<div class="bg-white rounded shadow p-6">
    {{-- Mensajes de error --}}
    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-100 text-red-700">
            <strong>Revisá los campos marcados:</strong>
            <ul class="list-disc pl-5 mt-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('clients.contacts.update', [$client, $contact]) }}" method="POST" autocomplete="off" class="space-y-4">
        @csrf
        @method('PUT')

        {{-- Nombre --}}
        <div>
            <label for="name" class="block text-sm font-medium text-gray-600">Nombre <span class="text-red-500">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $contact->name) }}" required
                   class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 @error('name') border-red-500 @enderror">
            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Correo --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-600">Correo</label>
            <input id="email" name="email" type="email" value="{{ old('email', $contact->email) }}"
                   class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 @error('email') border-red-500 @enderror">
            @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Teléfono --}}
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-600">Teléfono</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $contact->phone) }}"
                   class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 @error('phone') border-red-500 @enderror">
            @error('phone') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Botones --}}
        <div class="flex justify-end gap-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">💾 Actualizar</button>
            <a href="{{ route('clients.show', $client) }}" 
               class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-200">Cancelar</a>
        </div>
    </form>
</div>
@endsection
