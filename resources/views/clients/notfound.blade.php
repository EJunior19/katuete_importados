@extends('layout.admin')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <strong>🙍‍♂️ Cliente no encontrado</strong>
                </div>

                <div class="card-body text-center">
                    <p class="mb-4">El cliente que buscás no existe o fue eliminado.</p>
                    
                    <a href="{{ route('clients.index') }}" class="btn btn-primary">
                        ← Volver a la lista de clientes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
