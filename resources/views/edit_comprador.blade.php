@extends('layouts.app')

@section('title', 'Editar Comprador')

@section('styles')
    {{-- Enlazar el archivo CSS externo --}}
    <link href="{{ asset('css/compradores.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="container mt-5">
        <div class="header-container">
            <h1>Editar: {{ strtoupper($comprador->nombre) }}</h1>
            <a href="{{ route('compradores.index') }}" class="btn btn-primary">
                &lt; Volver
            </a>
        </div>

        <!-- Mostrar errores -->
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('comprador.update', $comprador->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <div class="section-border">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="{{ old('nombre', $comprador->nombre) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" value="{{ old('direccion', $comprador->direccion) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" value="{{ old('telefono', $comprador->telefono) }}" required>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="section-border">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $comprador->email) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="dni" class="form-label">DNI</label>
                            <input type="text" class="form-control" id="dni" name="dni" value="{{ old('dni', $comprador->dni) }}" required>
                        </div>

                        <!-- Ocultando la casilla pero manteniendo funcionalidad -->
                        <div class="judicializado-container">
                            <input type="hidden" name="judicializado" value="0">
                            <input type="checkbox" id="judicializado" name="judicializado" value="1" {{ old('judicializado', $comprador->judicializado) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>
@endsection 