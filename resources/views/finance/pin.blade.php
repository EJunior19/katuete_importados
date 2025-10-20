@extends('layout.admin')

@section('content')
<div class="max-w-sm mx-auto mt-10 bg-slate-900 border border-slate-700 rounded-xl p-6">
  <h1 class="text-xl font-semibold text-slate-100 mb-1">🔒 Acceso al Panel Financiero</h1>
  <p class="text-slate-400 text-sm mb-4">Ingresá el PIN de seguridad para continuar.</p>

  @if(session('warn'))
    <div class="mb-3 text-amber-300 text-sm">{{ session('warn') }}</div>
  @endif

  <form method="POST" action="{{ route('finance.pin.verify') }}" class="space-y-4">
    @csrf
    <div>
      <label class="block text-xs text-slate-400 mb-1">PIN</label>
      <input type="password" name="pin" inputmode="numeric" autocomplete="one-time-code"
             class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
             placeholder="••••" autofocus>
      @error('pin') <p class="text-rose-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <button type="submit"
      class="w-full px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium">
      Entrar
    </button>

    <a href="{{ route('dashboard.index') }}"
      class="block text-center text-slate-400 text-sm hover:text-slate-200 mt-1">Volver al panel</a>

    <p class="text-xs text-slate-500 mt-3">
      Por seguridad, el acceso con PIN caduca en {{ config('finance.ttl') }} minutos.
    </p>
  </form>
</div>
@endsection
