@extends('layout.admin')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Clientes eliminados</h3>
    <a href="{{ route('clients.index') }}" class="btn btn-secondary btn-sm">⬅ Volver</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Asignado a</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                @forelse ($clients as $client)
                <tr>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->email }}</td>
                    <td>{{ $client->phone }}</td>
                    <td>{{ $client->user->name ?? '—' }}</td>
                    <td>
                        <form action="{{ route('clients.activate', $client) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">Reingresar</button>
                        </form>

                        

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No hay clientes eliminados.</td>
                </tr>
                @endforelse
            </table>
        </div>

        @if (method_exists($clients, 'links'))
        <div class="mt-3">
            {{ $clients->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

@endsection