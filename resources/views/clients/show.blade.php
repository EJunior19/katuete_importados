{{-- resources/views/clients/show.blade.php --}}
@extends('layout.admin')

@section('content')
@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;

  // Documentos del cliente
  $docs = $client->documents()->latest()->get();

  // Agrupación: 1) primer tag como sección 2) heurística por nombre/mime
  $grouped = $docs->groupBy(function($d) {
    $tag = collect($d->tags ?? [])->filter()->first();
    if ($tag) return Str::title($tag);

    $name = Str::lower($d->original_name ?? '');
    $mime = Str::lower($d->mime ?? '');

    if (Str::contains($name, ['cedula','cédula','dni','ruc'])) return 'Identidad';
    if (Str::contains($name, ['contrato','agreement']))         return 'Contratos';
    if (Str::contains($name, ['factura','recibo','comprobante'])) return 'Comprobantes';
    if (Str::contains($name, ['credito','crédito','prestamo'])) return 'Créditos';
    if (Str::contains($mime, 'pdf') || Str::endsWith($name, '.pdf')) return 'PDFs';
    if (Str::contains($mime, 'image') || Str::contains($name, ['.jpg','.jpeg','.png','.webp'])) return 'Imágenes';
    if (Str::contains($name, ['.xls','.xlsx','.csv'])) return 'Hojas de cálculo';
    return 'Otros';
  });

  // Orden sugerido + anexar secciones no previstas al final
  $order  = collect(['Identidad','Contratos','Comprobantes','Créditos','PDFs','Imágenes','Hojas de cálculo','Otros']);
  $sorted = $order->mapWithKeys(fn($k) => [$k => $grouped->get($k, collect())])
                  ->filter(fn($c) => $c->isNotEmpty())
                  ->merge($grouped->reject(fn($c,$k) => $order->contains($k)));
@endphp

{{-- ======= Encabezado ======= --}}
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl md:text-3xl font-bold text-slate-100">
    👤 Cliente <span class="font-mono">#{{ $client->id }}</span>
  </h1>

  <div class="flex gap-2">
    <a href="{{ route('clients.index') }}"
       class="px-4 py-2 rounded-lg border border-slate-600 bg-slate-800 text-slate-100 hover:bg-slate-700 transition">
      ← Volver
    </a>
  </div>
</div>

<x-flash-message />

{{-- ======= Tarjeta de datos del cliente ======= --}}
<div class="bg-slate-900 text-slate-200 rounded-xl shadow-lg border border-slate-700 p-6 mb-6">
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-base leading-7">
    <p><span class="text-slate-400 text-xs uppercase">Código</span><br>
      <span class="text-slate-100 font-medium">{{ $client->code }}</span>
    </p>
    <p><span class="text-slate-400 text-xs uppercase">RUC / Cédula</span><br>
      <span class="text-slate-100 font-medium">{{ $client->ruc }}</span>
    </p>
    <p><span class="text-slate-400 text-xs uppercase">Nombre</span><br>
      <span class="text-slate-100 font-medium">{{ $client->name }}</span>
    </p>
    <p><span class="text-slate-400 text-xs uppercase">Email</span><br>
      <span class="text-slate-100 font-medium">{{ $client->email }}</span>
    </p>
    <p><span class="text-slate-400 text-xs uppercase">Teléfono</span><br>
      <span class="text-slate-100 font-medium">{{ $client->phone ?? '—' }}</span>
    </p>
    <p><span class="text-slate-400 text-xs uppercase">Dirección</span><br>
      <span class="text-slate-100 font-medium">{{ $client->address ?? '—' }}</span>
    </p>

    <p class="md:col-span-2">
      <span class="text-slate-400 text-xs uppercase">Notas</span><br>
      <span class="text-slate-100">{{ $client->notes ?? '—' }}</span>
    </p>

    <p>
      <span class="text-slate-400 text-xs uppercase">Estado</span><br>
      @php
        $activeColor = $client->active
          ? 'bg-emerald-600/15 text-emerald-300 border-emerald-700/40'
          : 'bg-rose-600/15 text-rose-300 border-rose-700/40';
      @endphp
      <span class="inline-flex items-center gap-2 text-xs mt-2 px-2 py-1 rounded border {{ $activeColor }}">
        {{ $client->active ? 'Activo' : 'Inactivo' }}
      </span>
    </p>

    <p>
      <span class="text-slate-400 text-xs uppercase">Telegram</span><br>
      @if($client->is_telegram_linked ?? false)
        <span class="inline-flex items-center gap-2 text-xs mt-2 px-2 py-1 rounded border bg-emerald-600/15 text-emerald-300 border-emerald-700/40">
          Vinculado
        </span>
        <code class="ml-2 bg-slate-800 px-2 py-1 rounded text-slate-200">{{ $client->telegram_chat_id }}</code>
      @else
        <span class="inline-flex items-center gap-2 text-xs mt-2 px-2 py-1 rounded border bg-slate-800 text-slate-200 border-slate-700">
          Sin vincular
        </span>
      @endif
    </p>
  </div>

  <p class="text-slate-400 text-xs mt-5">
    📅 Creado: {{ $client->created_at?->format('Y-m-d H:i') }}
    <span class="text-slate-600"> · </span>
    🔄 Actualizado: {{ $client->updated_at?->format('Y-m-d H:i') }}
  </p>

  {{-- Acciones --}}
  <div class="flex flex-wrap gap-3 mt-5">
    <x-action-buttons 
      :edit="route('clients.edit', $client)" 
      :delete="route('clients.destroy', $client)" 
      :name="'el cliente '.$client->name" />

    <a href="{{ route('clients.telegram.show', $client) }}"
       class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-500 transition">
      Telegram
    </a>

    <form method="POST" action="{{ route('clients.activate', $client) }}">
      @csrf
      <button class="px-4 py-2 rounded-lg border border-amber-600 text-amber-300 hover:bg-amber-600 hover:text-white transition">
        {{ $client->active ? 'Desactivar' : 'Activar' }}
      </button>
    </form>
  </div>
</div>

{{-- ======= Documentos (solo lectura) ======= --}}
<div class="mb-2 flex items-center gap-2">
  <h2 class="text-lg md:text-xl font-bold text-slate-100">📎 Documentos del cliente</h2>
  <span class="text-xs px-2 py-0.5 rounded border bg-slate-800 text-slate-200 border-slate-700">
    {{ $docs->count() }} documento{{ $docs->count()===1 ? '' : 's' }}
  </span>
</div>

@forelse($sorted as $section => $items)
  <div class="mb-6 rounded-xl overflow-hidden shadow-lg border border-slate-800 bg-slate-950">
    <div class="flex items-center justify-between px-4 md:px-5 py-3 bg-slate-900/70 border-b border-slate-800">
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></span>
        <h3 class="text-slate-100 font-semibold">{{ $section }}</h3>
      </div>
      <span class="text-xs px-2 py-0.5 rounded bg-slate-800 text-slate-200 border border-slate-700">
        {{ $items->count() }} doc{{ $items->count()===1 ? '' : 's' }}
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-900/80 text-slate-200 uppercase text-xs tracking-wide sticky top-0">
          <tr>
            <th class="px-4 py-3 text-left">Nombre</th>
            <th class="px-4 py-3 text-left">Tamaño</th>
            <th class="px-4 py-3 text-left">Tipo</th>
            <th class="px-4 py-3 text-left">Visibilidad</th>
            <th class="px-4 py-3 text-left">Subido</th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-800 text-slate-100">
  @foreach($items as $doc)
    @php
      // inferimos tipo visual
      $mime = $doc->mime;          // accessor (puede ser null)
      $ext  = $doc->ext;           // accessor
      [$typeLabel, $typeClass] = match (true) {
        is_string($mime) && Str::contains($mime, 'pdf')
          => ['PDF', 'bg-rose-600/15 text-rose-300 border border-rose-700/40'],
        is_string($mime) && Str::contains($mime, 'image')
          => ['Imagen', 'bg-indigo-600/15 text-indigo-300 border border-indigo-700/40'],
        in_array($ext, ['xls','xlsx','csv'])
          => ['Planilla', 'bg-emerald-600/15 text-emerald-300 border border-emerald-700/40'],
        default
          => [Str::upper($ext ?: 'FILE'), 'bg-slate-700/20 text-slate-200 border border-slate-600/40'],
      };

      // URL pública (según accessor). Si manejas privados, cambialo por tu route() protegida
      $downloadUrl = $doc->url;

      // si no tienes campo is_private en tu tabla, asumimos público
      $isPrivate = (bool) ($doc->is_private ?? false);
    @endphp

    <tr class="hover:bg-slate-900/60 transition">
      {{-- NOMBRE --}}
      <td class="px-4 py-3">
        <div class="font-medium text-slate-100">
          {{ $doc->display_name }} {{-- basename(file_path) --}}
        </div>
        @if(!empty($doc->type))
          <div class="text-[11px] mt-0.5 inline-block px-2 py-0.5 rounded border bg-slate-800 text-slate-300 border-slate-700">
            {{ $doc->type }}
          </div>
        @endif
      </td>

      {{-- TAMAÑO --}}
      <td class="px-4 py-3 text-slate-200 whitespace-nowrap">
        {{ $doc->size_kb ?? '—' }}
      </td>

      {{-- TIPO (chip) --}}
      <td class="px-4 py-3">
        <span class="px-2 py-0.5 text-xs rounded {{ $typeClass }}">{{ $typeLabel }}</span>
      </td>

      {{-- VISIBILIDAD --}}
      <td class="px-4 py-3">
        <span class="px-2 py-0.5 text-xs rounded
          {{ $isPrivate
              ? 'bg-amber-600/15 text-amber-300 border border-amber-700/40'
              : 'bg-emerald-600/15 text-emerald-300 border border-emerald-700/40' }}">
          {{ $isPrivate ? 'Privado' : 'Público' }}
        </span>
      </td>

      {{-- SUBIDO --}}
      <td class="px-4 py-3 text-slate-300 whitespace-nowrap">
        {{ optional($doc->created_at)->format('Y-m-d H:i') }}
      </td>

      {{-- ACCIONES --}}
      <td class="px-4 py-3 text-right whitespace-nowrap">
        <a href="{{ $downloadUrl }}" target="_blank"
           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 transition">
          ⬇️ Descargar
        </a>
        @if(!$isPrivate && ($doc->is_image ?? false))
          <a href="{{ $downloadUrl }}" target="_blank"
             class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-700 text-slate-100 hover:bg-slate-600 ml-2 transition">
            👁 Ver
          </a>
        @endif
      </td>
    </tr>
  @endforeach
</tbody>

      </table>
    </div>
  </div>
@empty
  <div class="rounded-xl border border-slate-700 bg-slate-900 p-6 text-slate-400">
    No hay documentos cargados para este cliente.
  </div>
@endforelse
@endsection
