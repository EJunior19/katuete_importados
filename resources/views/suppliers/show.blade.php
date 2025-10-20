@extends('layout.admin')

@section('content')
<div class="w-full px-6">
  <h1 class="text-3xl font-bold mb-6 text-green-400 flex items-center gap-2">
    🏭 Proveedor #{{ $supplier->id }}
  </h1>

  <x-flash-message />
  {{-- ================= DATOS GENERALES ================= --}}
  <div class="bg-gray-900 text-white rounded-xl shadow-2xl p-10 border-2 border-green-400 w-full">

    <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="space-y-6">
      @csrf
      @method('PUT')

      <div class="grid md:grid-cols-2 gap-6 text-lg">
        <div>
          <label class="block text-sm text-green-300 mb-1">Código</label>
          <input type="text" class="w-full bg-gray-800 border border-gray-700 rounded p-2 font-mono" value="{{ $supplier->code ?? '—' }}" readonly>
        </div>

        <div>
          <label class="block text-sm text-green-300 mb-1">Nombre</label>
          <input name="name" type="text" value="{{ old('name', $supplier->name) }}" required
                 class="w-full bg-gray-800 border border-gray-700 rounded p-2">
        </div>

        <div>
          <label class="block text-sm text-green-300 mb-1">RUC</label>
          <input name="ruc" type="text" value="{{ old('ruc', $supplier->ruc) }}"
                 class="w-full bg-gray-800 border border-gray-700 rounded p-2">
        </div>

        <div>
          <label class="block text-sm text-green-300 mb-1">Email (principal)</label>
          <input name="email" type="email"
              value="{{ old('email', $supplier->mainEmail->email ?? '') }}"
              class="w-full bg-gray-800 border border-gray-700 rounded p-2">
        </div>

        <div>
          <label class="block text-sm text-green-300 mb-1">Teléfono principal</label>
          <input name="phone" type="text"
              value="{{ old('phone', $supplier->primaryPhone->phone_number ?? '') }}"
              class="w-full bg-gray-800 border border-gray-700 rounded p-2">
        </div>

        <div>
          <label class="block text-sm text-green-300 mb-1">Dirección principal</label>
          <input name="address" type="text"
              value="{{ old('address', $supplier->primaryAddress->street ?? '') }}"
              class="w-full bg-gray-800 border border-gray-700 rounded p-2">
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm text-green-300 mb-1">Notas</label>
          <textarea name="notes" rows="2" class="w-full bg-gray-800 border border-gray-700 rounded p-2">{{ old('notes', $supplier->notes) }}</textarea>
        </div>

        <div>
          <label class="block text-sm text-green-300 mb-1">Activo</label>
          <select name="active" class="w-full bg-gray-800 border border-gray-700 rounded p-2">
            <option value="1" {{ $supplier->active ? 'selected' : '' }}>Sí</option>
            <option value="0" {{ !$supplier->active ? 'selected' : '' }}>No</option>
          </select>
        </div>

        <div>
          <label class="block text-sm text-green-300 mb-1">Monto total comprado</label>
          <div class="bg-gray-800 border border-gray-700 rounded p-2">
            ₲ {{ number_format($totals->total_amount ?? 0, 0, ',', '.') }}
          </div>
        </div>

        <div>
          <label class="block text-sm text-green-300 mb-1">Ítems comprados</label>
          <div class="bg-gray-800 border border-gray-700 rounded p-2">
            {{ number_format($totals->total_items ?? 0) }}
          </div>
        </div>

        <div>
          <label class="block text-sm text-green-300 mb-1">Compras aprobadas</label>
          <div class="bg-gray-800 border border-gray-700 rounded p-2">
            {{ $supplier->purchases_count ?? 0 }}
          </div>
        </div>
      </div>

      <div class="flex flex-wrap justify-between items-center mt-8">
        <button type="submit"
          class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2 rounded-lg shadow-lg">
          💾 Guardar cambios
        </button>

        <a href="{{ route('suppliers.index') }}"
           class="px-6 py-2 text-sm rounded-lg border border-gray-500 text-gray-300 hover:bg-gray-600 font-semibold shadow">
           ← Volver
        </a>
      </div>
    </form>

    <p class="text-gray-400 text-sm mt-6">
      📅 Creado: {{ $supplier->created_at?->format('d/m/Y H:i') }} ·
      🔄 Actualizado: {{ $supplier->updated_at?->format('d/m/Y H:i') }}
    </p>
  </div>

  {{-- ================= SUBSECCIONES ================= --}}
  <div class="mt-12 space-y-12">

    {{-- DIRECCIONES --}}
    <x-form-card title="Direcciones">
      <form method="POST" action="{{ route('suppliers.addresses.store', $supplier) }}" class="space-y-3">
        @csrf
        <div class="grid md:grid-cols-3 gap-3">
          <x-input-text name="street" label="Calle / Dirección" required />
          <x-input-text name="city" label="Ciudad" required />
          <x-input-text name="state" label="Departamento" />
          <x-input-text name="country" label="País" value="Paraguay" />
          <x-input-text name="postal_code" label="Código Postal" />
          <div>
            <label class="block text-sm mb-1">Tipo</label>
            <select name="type" class="w-full bg-gray-800 border border-gray-700 rounded p-2">
              <option value="fiscal">Fiscal</option>
              <option value="entrega">Entrega</option>
              <option value="sucursal">Sucursal</option>
            </select>
          </div>
          <label class="inline-flex items-center mt-6">
            <input type="checkbox" name="is_primary" class="mr-2"> Marcar como principal
          </label>
        </div>
        <x-submit-button text="Agregar dirección" />
      </form>

      <x-table class="mt-4">
        <x-slot:head>
          <tr>
            <th>Calle</th><th>Ciudad</th><th>Tipo</th><th>Principal</th><th class="text-right">Acciones</th>
          </tr>
        </x-slot:head>
        @forelse($supplier->addresses as $addr)
          <tr>
            <td>{{ $addr->street }}</td>
            <td>{{ $addr->city }}</td>
            <td><x-status-badge :text="ucfirst($addr->type)" /></td>
            <td>{{ $addr->is_primary ? 'Sí' : 'No' }}</td>
            <td class="text-right space-x-2">
              <x-delete-button :action="route('suppliers.addresses.destroy', [$supplier,$addr])" confirm="¿Eliminar esta dirección?" />
              @unless($addr->is_primary)
                <form method="POST" action="{{ route('suppliers.addresses.primary', [$supplier,$addr]) }}" class="inline">@csrf
                  <button type="submit" class="text-green-400 hover:text-green-200 text-sm">Hacer principal</button>
                </form>
              @endunless
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-gray-400">Sin direcciones registradas</td></tr>
        @endforelse
      </x-table>
    </x-form-card>

    {{-- TELÉFONOS --}}
    <x-form-card title="Teléfonos">
      <form method="POST" action="{{ route('suppliers.phones.store', $supplier) }}" class="space-y-3">
        @csrf
        <div class="grid md:grid-cols-3 gap-3">
          <x-input-text name="phone_number" label="Número" required />
          <div>
            <label class="block text-sm mb-1">Tipo</label>
            <select name="type" class="w-full bg-gray-800 border border-gray-700 rounded p-2">
              <option value="principal">Principal</option>
              <option value="secundario">Secundario</option>
              <option value="fax">Fax</option>
              <option value="whatsapp">WhatsApp</option>
            </select>
          </div>
          <label class="inline-flex items-center mt-6">
            <input type="checkbox" name="is_primary" class="mr-2"> Marcar como principal
          </label>
        </div>
        <x-submit-button text="Agregar teléfono" />
      </form>

      <x-table class="mt-4">
        <x-slot:head>
          <tr>
            <th>Número</th><th>Tipo</th><th>Activo</th><th>Principal</th><th class="text-right">Acciones</th>
          </tr>
        </x-slot:head>
        @forelse($supplier->phones as $ph)
          <tr>
            <td>{{ $ph->phone_number }}</td>
            <td><x-status-badge :text="ucfirst($ph->type)" /></td>
            <td>{{ $ph->is_active ? 'Sí' : 'No' }}</td>
            <td>{{ $ph->is_primary ? 'Sí' : 'No' }}</td>
            <td class="text-right space-x-2">
              <x-delete-button :action="route('suppliers.phones.destroy', [$supplier, $ph])" confirm="¿Eliminar este teléfono?" />
              @unless($ph->is_primary)
                <form method="POST" action="{{ route('suppliers.phones.primary', [$supplier,$ph]) }}" class="inline">@csrf
                  <button type="submit" class="text-green-400 hover:text-green-200 text-sm">Hacer principal</button>
                </form>
              @endunless
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-gray-400">Sin teléfonos cargados</td></tr>
        @endforelse
      </x-table>
    </x-form-card>

    {{-- CORREOS --}}
    <x-form-card title="Correos">
      <form method="POST" action="{{ route('suppliers.emails.store', $supplier) }}" class="space-y-3">
        @csrf
        <div class="grid md:grid-cols-3 gap-3">
          <x-input-text name="email" label="Correo" type="email" required />
          <div>
            <label class="block text-sm mb-1">Tipo</label>
            <select name="type" class="w-full bg-gray-800 border border-gray-700 rounded p-2">
              <option value="general">General</option>
              <option value="ventas">Ventas</option>
              <option value="compras">Compras</option>
              <option value="facturacion">Facturación</option>
            </select>
          </div>
          <label class="inline-flex items-center mt-6">
            <input type="checkbox" name="is_default" class="mr-2"> Marcar como principal del tipo
          </label>
        </div>
        <x-submit-button text="Agregar correo" />
      </form>

      <x-table class="mt-4">
        <x-slot:head>
          <tr>
            <th>Correo</th><th>Tipo</th><th>Activo</th><th>Principal</th><th class="text-right">Acciones</th>
          </tr>
        </x-slot:head>
        @forelse($supplier->emails as $em)
          <tr>
            <td>{{ $em->email }}</td>
            <td><x-status-badge :text="ucfirst($em->type)" /></td>
            <td>{{ $em->is_active ? 'Sí' : 'No' }}</td>
            <td>{{ $em->is_default ? 'Sí' : 'No' }}</td>
            <td class="text-right space-x-2">
              <x-delete-button :action="route('suppliers.emails.destroy', [$supplier,$em])" confirm="¿Eliminar este correo?" />
              @unless($em->is_default)
                <form method="POST" action="{{ route('suppliers.emails.default', [$supplier,$em]) }}" class="inline">@csrf
                  <button type="submit" class="text-green-400 hover:text-green-200 text-sm">Hacer principal</button>
                </form>
              @endunless
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-gray-400">Sin correos cargados</td></tr>
        @endforelse
      </x-table>
    </x-form-card>
  </div>

  {{-- ================= ÚLTIMAS COMPRAS ================= --}}
  <div class="mt-16">
    <h2 class="text-2xl font-bold text-green-400 mb-6 flex items-center gap-2">
      🧾 Últimas compras del proveedor
    </h2>

    @if(isset($latestPurchases) && $latestPurchases->count())
      <div class="overflow-x-auto rounded-xl border border-gray-600 bg-gray-950 shadow-lg">
        <table class="min-w-full text-sm text-gray-100">
          <thead class="bg-gray-800 text-gray-100 uppercase text-xs tracking-wide border-b border-gray-700">
            <tr>
              <th class="px-4 py-3 text-left">ID</th>
              <th class="px-4 py-3 text-left">Fecha</th>
              <th class="px-4 py-3 text-left">Estado</th>
              <th class="px-4 py-3 text-left"># Ítems</th>
              <th class="px-4 py-3 text-right">Total</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-800">
            @foreach($latestPurchases as $p)
              <tr class="hover:bg-gray-800/80 transition-colors duration-150">
                <td class="px-4 py-2 font-mono text-green-300">{{ $p->id }}</td>
                <td class="px-4 py-2">{{ $p->created_at?->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-2">
                  @php
                    $estadoColor = match($p->estado) {
                      'pendiente' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/40',
                      'aprobado'  => 'bg-green-500/20 text-green-400 border-green-500/40',
                      'rechazado' => 'bg-red-500/20 text-red-400 border-red-500/40',
                      default => 'bg-gray-500/20 text-gray-300 border-gray-500/40',
                    };
                  @endphp
                  <span class="px-3 py-1.5 rounded-lg text-xs font-semibold border {{ $estadoColor }}">
                    {{ ucfirst($p->estado) }}
                  </span>
                </td>
                <td class="px-4 py-2">{{ (int) ($p->items_count ?? 0) }}</td>
                <td class="px-4 py-2 text-right text-green-300 font-semibold">
                  ₲ {{ number_format($p->total, 0, ',', '.') }}
                </td>
                </td>
                <td class="px-4 py-2 text-right">
                  <a href="{{ route('purchases.show', $p->id) }}"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-green-500/60
                            text-green-400 hover:text-white hover:bg-green-600/70 text-xs font-semibold transition-all duration-150">
                    🔍 Ver detalle
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="bg-gray-900 border border-gray-700 text-gray-300 p-6 rounded-lg text-center">
        <p class="text-lg">No hay compras recientes de este proveedor.</p>
        <p class="text-sm text-gray-400 mt-1">Las compras aprobadas aparecerán aquí automáticamente.</p>
      </div>
    @endif
  </div>

</div>
@endsection
