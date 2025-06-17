@extends('layouts.app')

@section('title', 'Próximos a Finalizar | SIMA Contable')

@section('styles')
    <!-- Estilos de informes -->
    <link rel="stylesheet" href="{{ asset('css/informes.css') }}">
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Compradores Próximos a Finalizar</h5>
            <div class="d-flex align-items-center">
                <input type="text" id="nombreBusqueda" class="form-control form-control-sm me-2" placeholder="Buscar por nombre...">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="tablaProximos">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user icon-column"></i> Comprador</th>
                            <th><i class="far fa-envelope icon-column"></i> Email</th>
                            <th><i class="fas fa-phone icon-column"></i> Teléfono</th>
                            <th>Lote</th>
                            <th class="titulo-con-accion">
                                Cuotas Faltantes
                                <button class="btn-finalizados" id="mostrarFinalizados">
                                    <i class="fas fa-flag-checkered btn-finalizados-icon"></i>
                                </button>
                            </th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detallesCompradores as $detalle)
                            <tr class="comprador-row {{ $detalle['finalizado'] ? 'finalizado-row' : '' }}" data-finalizado="{{ $detalle['finalizado'] ? '1' : '0' }}">
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
                                    @if($detalle['estadisticas']['parciales'] > 0)
                                        <span class="badge bg-warning badge-count">
                                            <i class="fas fa-hand-holding-usd"></i> {{ $detalle['estadisticas']['parciales'] }}
                                        </span>
                                    @endif
                                    @if($detalle['estadisticas']['pendientes'] > 0)
                                        <span class="badge bg-danger badge-count">
                                            <i class="fas fa-exclamation-triangle"></i> {{ $detalle['estadisticas']['pendientes'] }}
                                        </span>
                                    @endif
                                    @if($detalle['estadisticas']['futuras'] > 0)
                                        <span class="badge bg-secondary badge-count">
                                            <i class="fas fa-clock"></i> {{ $detalle['estadisticas']['futuras'] }}
                                        </span>
                                    @endif
                                    @if($detalle['finalizado'])
                                        <span class="badge bg-success">Finalizado</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('comprador.show', $detalle['comprador']->id) }}" class="btn btn-primary btn-sm" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="mailto:{{ $detalle['comprador']->email }}" class="btn btn-mail-light btn-sm" title="Enviar mensaje">
                                            <i class="far fa-envelope"></i>
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
@endsection

@push('scripts')
<script src="{{ asset('js/proximos_a_finalizar.js') }}"></script>
@endpush