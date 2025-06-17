@extends('layouts.app')

@section('title', 'Lotes Vendidos')

@section('styles')
<style>
    .card-header {
        background-color: #198754 !important;
        color: white;
    }
    
    /* Quitar borde celeste al enfocar el input */
    #dtSearchBox:focus {
        box-shadow: none;
        border-color: #ced4da; /* Color de borde normal de Bootstrap */
    }
    
    /* Personalizar la apariencia del buscador para lotes */
    .input-group-text {
        background-color: #198754 !important;
        color: white;
    }
</style>
@endsection

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Lotes Vendidos</h5>
        </div>
        <div class="card-body">
            <!-- Buscador similar al de compradores -->
            <div class="row justify-content-start mb-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="dtSearchBox" class="form-control" 
                               placeholder="Buscar lote o comprador...">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="tablaLotes">
                    <thead class="table-light">
                        <tr>
                            <th>Lote</th>
                            <th>Manzana</th>
                            <th>Loteo</th>
                            <th>Comprador</th>
                            <th>Precio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lotes as $lote)
                            <tr>
                                <td>{{ $lote->lote }}</td>
                                <td>{{ $lote->manzana }}</td>
                                <td>{{ $lote->loteo }}</td>
                                <td>
                                    @if($lote->comprador)
                                        {{ $lote->comprador->nombre }} {{ $lote->comprador->apellido }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td data-order="{{ $lote->comprador && $lote->comprador->financiacion ? $lote->comprador->financiacion->monto_a_financiar : 0 }}">
                                    @if($lote->comprador && $lote->comprador->financiacion)
                                        U$D {{ number_format($lote->comprador->financiacion->monto_a_financiar, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($lote->comprador)
                                        <a href="{{ route('comprador.show', $lote->comprador_id) }}" 
                                           class="btn btn-sm btn-primary text-white" 
                                           data-bs-toggle="tooltip"
                                           title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @else
                                        <button class="btn btn-sm btn-secondary" disabled>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No hay lotes registrados</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/lotes_index.js') }}"></script>
@endsection