{{-- resources/views/components/action-buttons.blade.php --}}
@props(['show' => null, 'edit' => null, 'delete' => null, 'back' => null, 'name' => 'este registro'])

<div class="flex justify-end items-center gap-2">
  {{-- Ver --}}
  @if($show)
    <a href="{{ $show }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-sky-400 text-sky-400 hover:bg-sky-500 hover:text-white transition">
      👁 Ver
    </a>
  @endif

  {{-- Editar --}}
  @if($edit)
    <a href="{{ $edit }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-amber-400 text-amber-400 hover:bg-amber-500 hover:text-white transition">
      ✏️ Editar
    </a>
  @endif

  {{-- Eliminar --}}
  @if($delete)
    <x-delete-button :action="$delete" :name="$name" />
  @endif

  {{-- Volver --}}
  @if($back)
    <a href="{{ $back }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-400 text-gray-300 hover:bg-gray-600 hover:text-white transition">
      ← Volver
    </a>
  @endif
  {{-- 🔌 Acciones extra inyectadas por el llamador (Aprobar/Rechazar/etc.) --}}
  {{ $slot }}
</div>
