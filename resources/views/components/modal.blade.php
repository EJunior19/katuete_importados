{{-- Modal con Alpine.js --}}
<div x-data="{ open: false }">
  <button @click="open = true" class="px-3 py-1.5 bg-blue-600 text-white rounded">Abrir modal</button>

  <div x-show="open" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" x-cloak>
    <div class="bg-gray-900 rounded-lg shadow-lg w-full max-w-lg p-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-200">{{ $title ?? 'Modal' }}</h2>
        <button @click="open = false" class="text-gray-400 hover:text-gray-200">&times;</button>
      </div>
      <div class="text-gray-300">
        {{ $slot }}
      </div>
    </div>
  </div>
</div>
