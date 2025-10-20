{{-- resources/views/layout/admin.blade.php --}}
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title','Dashboard · CRM Katuete')</title>

  {{-- App styles/scripts (Vite) --}}
  @vite(['resources/css/app.css','resources/js/app.js'])

  {{-- Hooks por página --}}
  @stack('head')
  @stack('meta')
  @stack('styles')
</head>
<body x-data="{ sidebarOpen: false }" class="min-h-screen bg-gray-100 text-gray-900">
  {{-- ===== Topbar ===== --}}
  <header class="sticky top-0 z-40 bg-white border-b shadow-sm">
    <nav class="flex items-center justify-between px-4 py-3">
      <div class="flex items-center gap-3">
        {{-- Hamburguesa (móvil) --}}
        <button
          type="button"
          class="md:hidden inline-flex items-center justify-center rounded-md p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
          @click="sidebarOpen = true"
          aria-controls="sidebar"
          :aria-expanded="sidebarOpen"
        >
          <span class="sr-only">Abrir menú</span>
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <a href="{{ route('dashboard.index') }}" class="font-bold text-lg">
          ERP Katuete Importados
        </a>

        {{-- Slot opcional de breadcrumbs/acciones pequeñas --}}
        @hasSection('breadcrumbs')
          <div class="hidden md:block border-l pl-3 ml-3 text-sm text-black-500">
            @yield('breadcrumbs')
          </div>
        @endif
      </div>

      {{-- Buscador (placeholder; apunta a /dashboard por GET si no tenés ruta aún) --}}
      <form class="hidden md:block w-1/3" role="search" method="GET" action="{{ url('/dashboard') }}">
        <div class="flex">
          <input
            type="text"
            name="q"
            placeholder="Buscar…"
            class="w-full rounded-l-md border-gray-300 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-3 rounded-r-md">
            <span class="sr-only">Buscar</span>
            <i class="fas fa-search"></i>
          </button>
        </div>
      </form>

      {{-- Área de acciones a la derecha (opcional por vista) --}}
      <div class="hidden md:flex items-center gap-2">
        @yield('toolbar')
      </div>
    </nav>
  </header>

  <div class="flex min-h-[calc(100vh-56px)]">
    {{-- ===== Sidebar ===== --}}
    <div
      id="sidebar"
      class="relative z-30"
      x-cloak
    >
      {{-- Overlay móvil --}}
      <div
        class="fixed inset-0 bg-black/40 md:hidden"
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen=false"
        aria-hidden="true"
      ></div>

      {{-- Contenedor sidebar --}}
      <aside
        class="fixed md:static inset-y-0 left-0 w-72 md:w-64 bg-white md:bg-transparent md:border-r md:border-gray-200 transform md:transform-none transition-transform md:transition-none"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        x-trap.noscroll.inert="sidebarOpen"
        @keydown.escape.window="sidebarOpen=false"
        aria-label="Menú lateral"
      >
        @include('layout.menu')
      </aside>
    </div>

    {{-- ===== Contenido principal ===== --}}
    <div class="flex-1 flex flex-col">
      <main class="flex-1 p-6">
        {{-- Mensajes flash globales --}}
        <x-flash-message />

        @yield('content')
      </main>

      {{-- ===== Footer ===== --}}
      <footer class="bg-white border-t py-4 px-6 text-sm text-gray-500 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
        <div>© {{ date('Y') }} CRM Katuete</div>
        <div class="space-x-3">
          <a href="#!" class="hover:text-gray-700">Política de Privacidad</a>
          <span aria-hidden="true">&middot;</span>
          <a href="#!" class="hover:text-gray-700">Términos y Condiciones</a>
        </div>
      </footer>
    </div>
  </div>

  {{-- Alpine.js (defer) --}}
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- Confirmación global de formularios con clase .delete-form o data-confirm --}}
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Delegación: funciona también con elementos agregados dinámicamente
      document.body.addEventListener('submit', (e) => {
        const form = e.target.closest('form');
        if (!form) return;

        const needsConfirm = form.classList.contains('delete-form') || form.dataset.confirm;
        if (!needsConfirm) return;

        e.preventDefault();

        const name = form.getAttribute('data-name') || form.dataset.name || 'este registro';
        const text = form.dataset.confirm || `Se eliminará ${name}. Esta acción no se puede deshacer.`;

        Swal.fire({
          title: '¿Estás seguro?',
          text,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          if (result.isConfirmed) form.submit();
        });
      }, { passive: false });
    });
  </script>

  {{-- Hooks por página --}}
  @stack('scripts')
</body>
</html>
{{-- resources/views/components/action-buttons.blade.php --}}