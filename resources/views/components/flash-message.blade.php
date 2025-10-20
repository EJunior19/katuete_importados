{{-- Mensajes flash (session) --}}
@if(session('ok'))
  <div class="mb-4 px-4 py-2 bg-green-100 text-green-800 border border-green-300 rounded text-sm shadow">
    {{ session('ok') }}
  </div>
@endif

@if(session('error'))
  <div class="mb-4 px-4 py-2 bg-red-100 text-red-800 border border-red-300 rounded text-sm shadow">
    {{ session('error') }}
  </div>
@endif
