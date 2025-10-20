@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    ⚠️ Cliente con datos incompletos
                </div>

                <div class="card-body text-center">
                    <p class="mb-4">
                        El cliente <strong>{{ $cliente->nombre ?? 'Desconocido' }}</strong> 
                        no tiene todos los datos requeridos para continuar.
                    </p>

                    <a href="{{ route('clients.edit', $cliente->id) }}" class="btn btn-warning">
                        Completar datos
                    </a>

                    <a href="{{ route('clients.index') }}" class="btn btn-secondary ms-2">
                        Volver a la lista
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
