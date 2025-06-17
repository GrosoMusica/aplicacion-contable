@extends('layouts.app')

@section('title', 'Compradores')

@section('styles')
    <!-- CSS personalizado para compradores -->
    <link rel="stylesheet" href="{{ asset('css/compradores.css') }}">

@endsection

@section('content')
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Compradores Registrados</h5>
            </div>
            <div class="card-body">
                <!-- Buscador mejorado de DataTables (simplificado) -->
                <div class="row justify-content-start">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="dtSearchBox" class="form-control" 
                                   placeholder="Búsqueda por Nombre o Email">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="tablaCompradores">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Lote</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($compradores as $comprador)
                            <tr class="{{ $comprador->judicializado ? 'judicializado' : '' }}">
                                <td>{{ $comprador->nombre }}</td>
                                <td>{{ $comprador->email }}</td>
                                <td>{{ $comprador->telefono }}</td>
                                <td>
                                    @if($comprador->lote)
                                        Mza: {{ $comprador->lote->manzana }} - Lote: {{ $comprador->lote->lote }}
                                    @else
                                        No asignado
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('comprador.show', $comprador->id) }}" class="btn btn-primary btn-sm btn-accion" title="Ver detalle">
                                        <i class="fas fa-eye text-white"></i>
                                    </a>
                                    <a href="{{ route('comprador.edit', $comprador->id) }}" class="btn btn-warning btn-sm btn-accion" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('pagos.index', ['comprador_id' => $comprador->id]) }}" class="btn btn-success btn-sm btn-accion" title="Registrar pago">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/compradores_index.js') }}"></script>
@endsection 