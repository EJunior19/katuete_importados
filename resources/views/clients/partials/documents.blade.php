<div class="bg-slate-900 text-slate-200 rounded-xl border border-slate-700 p-6">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-lg md:text-xl font-bold text-slate-100">
      📎 Documentos de <span class="font-mono">{{ $client->name }}</span>
    </h2>
    <a href="{{ route('clients.edit', $client) }}"
       class="px-3 py-1.5 rounded-lg border border-slate-600 bg-slate-800 text-slate-100 hover:bg-slate-700 text-sm transition">
      ← Volver a datos
    </a>
  </div>

  {{-- Formulario de subida --}}
  <form method="POST"
        action="{{ route('clients.documents.store', $client) }}"
        enctype="multipart/form-data"
        class="grid md:grid-cols-3 gap-3 items-end mb-5">
    @csrf

    <div>
      <label class="block text-xs uppercase text-slate-400 mb-1">Tipo</label>
      <select name="type" required
              class="w-full rounded-lg bg-slate-950 border border-slate-700 focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2 text-sm text-slate-100">
        <option value="cedula">Cédula</option>
        <option value="comprobante_ingreso">Comprobante de ingreso</option>
        <option value="residencia">Constancia de residencia</option>
        <option value="referencias">Referencias</option>
        <option value="otros" selected>Otros</option>
      </select>
    </div>

    <div class="md:col-span-2">
      <label class="block text-xs uppercase text-slate-400 mb-1">Archivos</label>
      <input type="file" name="files[]" multiple required
             accept=".pdf,.doc,.docx,.odt,.jpg,.jpeg,.png,.webp"
             class="w-full rounded-lg bg-slate-950 border border-slate-700 focus:border-emerald-500 focus:ring-emerald-500/30 px-3 py-2 text-sm text-slate-100
                    file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-emerald-600/15 file:text-emerald-300 hover:file:bg-emerald-600/25">
      <p class="text-xs text-slate-400 mt-1">
        Permitidos: PDF, DOC, DOCX, ODT, JPG, PNG, WEBP (máx. 10MB c/u).
      </p>
    </div>

    <div class="md:col-span-3 flex justify-end">
      <button class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm transition">
        ⬆️ Subir
      </button>
    </div>
  </form>

  {{-- Listado de documentos --}}
  <div class="rounded-lg border border-slate-800 overflow-hidden">
    <div class="px-4 py-2 border-b border-slate-800 text-sm text-slate-200 flex items-center justify-between bg-slate-950/70">
      <span>Archivos subidos</span>
      <span class="text-xs px-2 py-0.5 rounded border bg-slate-900 text-slate-300 border-slate-700">
        {{ $client->documents->count() }} archivo(s)
      </span>
    </div>

    <div class="divide-y divide-slate-800">
      @forelse($client->documents as $doc)
        @php
          $ext = $doc->ext; // accessor del modelo
          $iconTxt = match($ext) {
            'pdf' => 'PDF',
            'doc', 'docx', 'odt' => 'Word/ODT',
            'jpg','jpeg','png','webp' => 'Imagen',
            default => Str::upper($ext ?: 'FILE'),
          };
          $iconClass = match($ext) {
            'pdf' => 'bg-rose-600/15 text-rose-300 border-rose-700/40',
            'doc','docx','odt' => 'bg-indigo-600/15 text-indigo-300 border-indigo-700/40',
            'jpg','jpeg','png','webp' => 'bg-emerald-600/15 text-emerald-300 border-emerald-700/40',
            default => 'bg-slate-700/20 text-slate-200 border-slate-600/40',
          };
          $sizeKB = isset($doc->size) ? number_format(($doc->size)/1024,1) . ' KB' : '';
        @endphp

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 px-4 py-2 hover:bg-slate-900/50 transition">
          <div class="flex items-start md:items-center gap-3">
            <span class="px-2 py-0.5 text-[11px] rounded-full border {{ $iconClass }}">
              {{ $iconTxt }}
            </span>

            <div class="min-w-0">
              <div class="flex items-center gap-2">
                @if(!empty($doc->type))
                  <span class="text-[11px] px-2 py-0.5 rounded border bg-slate-800 text-slate-200 border-slate-700">{{ $doc->type }}</span>
                @endif
                @if($sizeKB)
                  <span class="text-[11px] px-2 py-0.5 rounded border bg-slate-800 text-slate-300 border-slate-700">{{ $sizeKB }}</span>
                @endif
                @if(!empty($doc->is_private))
                  <span class="text-[11px] px-2 py-0.5 rounded border bg-amber-600/15 text-amber-300 border-amber-700/40">Privado</span>
                @else
                  <span class="text-[11px] px-2 py-0.5 rounded border bg-emerald-600/15 text-emerald-300 border-emerald-700/40">Público</span>
                @endif>
              </div>

              <a href="{{ $doc->url }}" target="_blank"
                 class="block text-emerald-300 hover:text-emerald-200 hover:underline truncate">
                {{ basename($doc->file_path) }}
              </a>

              @if(!empty($doc->created_at) || !empty($doc->uploader?->name))
                <div class="text-xs text-slate-400">
                  {{ optional($doc->created_at)->format('Y-m-d H:i') }}
                  @if($doc->uploader?->name)
                    <span class="text-slate-600"> · </span>{{ $doc->uploader->name }}
                  @endif
                </div>
              @endif
            </div>
          </div>

          <div class="flex items-center gap-2 md:justify-end">
            <a href="{{ $doc->url }}" target="_blank"
               class="px-3 py-1.5 rounded-lg border border-slate-600 bg-slate-800 text-slate-100 hover:bg-slate-700 text-xs transition">
               👁 Ver/Descargar
            </a>

            <form method="POST" action="{{ route('clients.documents.destroy', [$client, $doc]) }}"
                  onsubmit="return confirm('¿Eliminar documento?')">
              @csrf @method('DELETE')
              <button class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs transition">
                Eliminar
              </button>
            </form>
          </div>
        </div>
      @empty
        <div class="px-4 py-6 text-center text-slate-400 text-sm">Sin documentos.</div>
      @endforelse
    </div>
  </div>
</div>
