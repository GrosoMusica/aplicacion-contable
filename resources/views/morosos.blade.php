@extends('layouts.app')

@section('title', 'Compradores Morosos')

@section('styles')
    <!-- Estilos de informes -->
    <link rel="stylesheet" href="{{ asset('css/informes.css') }}">
    <!-- CSS específico para morosos -->
    <link rel="stylesheet" href="{{ asset('css/morosos.css') }}">
@endsection

@section('content')
<div class="container mt-4">
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Compradores Morosos</h5>
            <div class="d-flex align-items-center">
                <input type="text" id="nombreBusqueda" class="form-control form-control-sm me-2" placeholder="Buscar por nombre...">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="tablaMorosos">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user icon-column"></i> Comprador</th>
                            <th><i class="far fa-envelope icon-column"></i> Email</th>
                            <th><i class="fas fa-phone icon-column"></i> Teléfono</th>
                            <th>Lote</th>
                            <th class="d-flex justify-content-between align-items-center">
                                Estado de Cuotas
                                <div>
                                    <button class="btn btn-success btn-sm" id="filtrarTodos" title="Mostrar todos">
                                        <i class="fas fa-asterisk"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" id="filtrarDos">2</button>
                                    <button class="btn btn-danger btn-sm" id="filtrarTresOMas">3+</button>
                                </div>
                            </th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detallesCompradores as $detalle)
                            <tr class="moroso-row" data-pendientes="{{ $detalle['estadisticas']['pendientes'] }}">
                                <td>
                                    {{ $detalle['comprador']->nombre }}
                                    <br>
                                    <span class="badge bg-primary cuotas-badge">
                                        {{ $detalle['estadisticas']['total'] }} cuotas totales
                                    </span>
                                </td>
                                <td>
                                    {{ $detalle['comprador']->email }}
                                </td>
                                <td>
                                    {{ $detalle['comprador']->telefono }}
                                </td>
                                <td>
                                    @if($detalle['comprador']->lote)
                                        Mza: {{ $detalle['comprador']->lote->manzana }} - Lote: {{ $detalle['comprador']->lote->lote }}
                                    @else
                                        No asignado
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-success badge-count">
                                        <i class="fas fa-check-circle"></i> {{ $detalle['estadisticas']['pagadas'] }}
                                    </span>
                                    @if($detalle['estadisticas']['parciales'] > 0)
                                        <span class="badge bg-warning badge-count">
                                            <i class="fas fa-hand-holding-usd"></i> {{ $detalle['estadisticas']['parciales'] }}
                                        </span>
                                    @endif
                                    <span class="badge bg-danger badge-count">
                                        <i class="fas fa-exclamation-triangle"></i> {{ $detalle['estadisticas']['pendientes'] }}
                                    </span>
                                    @if($detalle['estadisticas']['futuras'] > 0)
                                        <span class="badge bg-secondary badge-count">
                                            <i class="fas fa-clock"></i> {{ $detalle['estadisticas']['futuras'] }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('comprador.show', $detalle['comprador']->id) }}" class="btn btn-primary btn-sm" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="mailto:{{ $detalle['comprador']->email }}" class="btn btn-warning btn-sm" title="Enviar mensaje">
                                            <i class="far fa-envelope"></i>
                                        </a>
                                        <a href="{{ route('pagos.index', ['comprador_id' => $detalle['comprador']->id]) }}" class="btn btn-success btn-sm" title="Registrar pago">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                    </div>
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
<script src="{{ asset('js/morosos.js') }}"></script>
@endsection