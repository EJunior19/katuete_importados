@props([
  'href' => null,
  'text' => '',
  'icon' => null,
  'color' => 'sky',     // sky | amber | indigo | emerald | rose | slate
  'target' => null,
  'disabled' => false,
])

@php
  $palette = [
    'sky'     => 'border-sky-600/40 text-sky-300 hover:bg-sky-900/30',
    'amber'   => 'border-amber-600/40 text-amber-300 hover:bg-amber-900/30',
    'indigo'  => 'border-indigo-600/40 text-indigo-300 hover:bg-indigo-900/30',
    'emerald' => 'bg-emerald-600 hover:bg-emerald-700 text-white border-transparent',
    'rose'    => 'border-rose-600/50 text-rose-300 hover:bg-rose-900/30',
    'slate'   => 'bg-slate-800 text-slate-500 cursor-not-allowed border-slate-700',
  ];
  $classes = "inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition border ".
             ($palette[$color] ?? $palette['sky']);
@endphp

@if($disabled || !$href)
  <button type="button" disabled class="{{ $classes }}">
    @if($icon)<span>{{ $icon }}</span>@endif
    {{ $text }}
  </button>
@else
  <a href="{{ $href }}" class="{{ $classes }}" @if($target) target="{{ $target }}" @endif>
    @if($icon)<span>{{ $icon }}</span>@endif
    {{ $text }}
  </a>
@endif
