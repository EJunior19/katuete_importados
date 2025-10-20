{{-- resources/views/components/status-badge.blade.php --}}
@props([
  'color' => null,  // color opcional (si no se pasa, se elige por estado)
  'label' => null,  // texto del estado (puede venir en español o inglés)
  'text'  => null,  // alias por compatibilidad
])

@php
  use Illuminate\Support\Str;

  // 1) Texto crudo y normalizado
  $raw   = $label ?? $text ?? '';
  $raw   = is_string($raw) ? $raw : '';                  // aseguro string
  $norm  = Str::of($raw)->trim()->lower()->toString();   // <- a STRING

  // 2) Mapa estado -> color (ES/EN)
  $stateToColor = [
    'aprobado'   => 'emerald',  'approved'  => 'emerald',
    'pagado'     => 'emerald',  'paid'      => 'emerald',
    'pendiente'  => 'amber',    'pending'   => 'amber',
    'rechazado'  => 'red',      'rejected'  => 'red',
    'vencido'    => 'red',      'overdue'   => 'red',
    'activo'     => 'emerald',  'active'    => 'emerald',
    'inactivo'   => 'gray',     'inactive'  => 'gray',
    'cancelado'  => 'red',      'cancelled' => 'red',
    'finalizado' => 'blue',     'finished'  => 'blue',
  ];

  // 3) Color efectivo
  $autoColor = $stateToColor[$norm] ?? null;
  $effColor  = $color ?: ($autoColor ?: 'gray');

  // 4) Clases por color
  $palette = [
    'emerald' => 'bg-emerald-600/20 text-emerald-300 border border-emerald-600/40',
    'red'     => 'bg-red-600/20 text-red-300 border border-red-600/40',
    'amber'   => 'bg-amber-500/20 text-amber-300 border border-amber-500/40',
    'blue'    => 'bg-blue-600/20 text-blue-300 border border-blue-600/40',
    'gray'    => 'bg-gray-600/20 text-gray-300 border border-gray-600/40',
  ];
  $style = $palette[$effColor] ?? $palette['gray'];

  // 5) Texto a mostrar
  $display = $raw !== '' ? ucfirst($raw) : '—';
@endphp

<span class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $style }}">
  {{ $display }}
</span>
