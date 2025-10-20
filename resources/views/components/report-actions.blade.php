@props(['route'])

<div class="flex gap-2">
  {{-- Botón imprimir --}}
  <a href="{{ $route }}?export=print" target="_blank"
     class="px-3 py-1.5 rounded-md border border-indigo-500 text-indigo-400 hover:bg-indigo-900/40 transition">
    🖨️ Imprimir
  </a>

  {{-- Botón PDF --}}
  <a href="{{ $route }}?export=pdf" target="_blank"
     class="px-3 py-1.5 rounded-md border border-red-500 text-red-400 hover:bg-red-900/40 transition">
    📄 PDF
  </a>

  {{-- Botón Excel --}}
  <a href="{{ $route }}?export=excel" target="_blank"
     class="px-3 py-1.5 rounded-md border border-green-500 text-green-400 hover:bg-green-900/40 transition">
    📊 Excel
  </a>
</div>
