{{-- Botón submit principal --}}
@props(['text' => 'Guardar'])

<button type="submit"
        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm rounded-lg shadow transition">
  💾 {{ $text }}
</button>
