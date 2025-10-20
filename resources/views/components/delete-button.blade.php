{{-- Botón eliminar con SweetAlert --}}
@props(['action', 'name' => 'este registro'])

<form action="{{ $action }}" method="POST" class="delete-form inline" data-name="{{ $name }}">
  @csrf
  @method('DELETE')
  <button type="submit"
          class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-red-600 text-red-500 hover:bg-red-600 hover:text-white transition">
    🗑 Eliminar
  </button>
</form>
