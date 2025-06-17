@extends('layouts.app')

@section('title', 'Gestión de Acreedores')

@section('styles')
    <!-- Estilos específicos para acreedores -->
    <link rel="stylesheet" href="{{ asset('css/acreedores.css') }}">

@endsection

@section('content')
<div class="row">
    <!-- Panel principal (75%) -->
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gestión de Acreedores</h5>
            </div>
            <div class="card-body">
                <!-- Pestañas de navegación para acreedores -->
                <ul class="nav nav-tabs acreedor-tabs mb-3" id="acreedoresTabs" role="tablist">
                    @foreach($acreedores as $index => $acreedor)
                        @if($acreedor->id != 1)  {{-- Excluir al admin --}}
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $index == 0 ? 'active' : '' }}" 
                                        id="tab-{{ $acreedor->id }}" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#content-{{ $acreedor->id }}" 
                                        type="button" 
                                        role="tab" 
                                        aria-controls="content-{{ $acreedor->id }}" 
                                        aria-selected="{{ $index == 0 ? 'true' : 'false' }}"
                                        data-id="{{ $acreedor->id }}">
                                    {{ $acreedor->nombre }}
                                </button>
                            </li>
                        @endif
                    @endforeach
                </ul>
                
                <!-- Contenido de las pestañas -->
                <div class="tab-content" id="acreedoresTabContent">
                    @foreach($acreedores as $index => $acreedor)
                        @if($acreedor->id != 1)  {{-- Excluir al admin --}}
                            <div class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}" 
                                 id="content-{{ $acreedor->id }}" 
                                 role="tabpanel" 
                                 aria-labelledby="tab-{{ $acreedor->id }}">
                                
                                <!-- Información del acreedor -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h4>{{ $acreedor->nombre }}</h4>
                                        <div class="acreedor-fecha-creacion">
                                            <i class="fas fa-calendar-alt me-2"></i>
                                            <span class="fecha-label">Fecha de Inicio:</span>
                                            <span class="fecha-valor">{{ \Carbon\Carbon::parse($acreedor->created_at)->format('d M, Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <div class="row">
                                            @php
                                                $calculosController = new \App\Http\Controllers\AcreedorCalculosController();
                                                $resumen = $calculosController->obtenerResumenAcreedor($acreedor->id);
                                                $totalACobrar = $resumen['total_a_cobrar'];
                                                $totalCobrado = $resumen['total_cobrado'];
                                            @endphp
                                            
                                            <div class="col-md-6">
                                                <div class="text-muted mb-1">Total cobrado</div>
                                                <h3 class="text-success fw-bold">U$D {{ number_format($totalCobrado, 2) }}</h3>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="text-muted mb-1">Total a cobrar</div>
                                                <h4 class="text-info fw-bold">U$D {{ number_format($totalACobrar, 2) }}</h4>
                                                <div class="d-flex justify-content-end mt-2">
                                                    <button class="btn btn-sm btn-outline-success me-2" title="Registrar pago">
                                                        <i class="fas fa-dollar-sign"></i> Abonar
                                                    </button>
                                                    <a href="{{ route('acreedores.export-distribucion', ['acreedor' => $acreedor->id]) }}" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Generar PDF"
                                                       target="_blank">
                                                        <i class="fas fa-file-pdf"></i> PDF
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Distribución de Ingresos con indicador de mes actual -->
                                <div class="border-bottom pb-2 mb-3 d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 d-flex align-items-center">
                                        <i class="fas fa-chart-pie text-primary me-2"></i>
                                        Distribución de Ingresos
                                    </h5>
                                    <div class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i> Mes Actual: {{ ucfirst(now()->locale('es')->isoFormat('MMMM YYYY')) }}
                                    </div>
                                </div>
                                
                                <!-- Botones para mostrar/ocultar pendientes y totales -->
                                <div class="mb-3 d-flex justify-content-end">
                                    <button class="btn btn-sm btn-outline-danger me-2 togglePendientes" 
                                            id="togglePendientes-{{ $acreedor->id }}" 
                                            data-acreedor-id="{{ $acreedor->id }}">
                                        <i class="fas fa-eye me-1"></i> Mostrar Pendientes
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary toggleTotales" 
                                            id="toggleTotales-{{ $acreedor->id }}" 
                                            data-acreedor-id="{{ $acreedor->id }}">
                                        <i class="fas fa-list-alt me-1"></i> Ver Totales
                                    </button>
                                </div>
                                
                                <!-- Tabla de distribución de ingresos para este acreedor -->
                                <div class="mb-4">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                @php
                                                    $montoTotalMes = 0;
                                                    $montoPagadoTotal = 0;
                                                    $montoPendienteTotal = 0;
                                                    $estadoGeneral = 'pendiente';
                                                @endphp
                                                
                                                @foreach($acreedor->financiaciones as $item)
                                                    @if($item->estado != 'sin_cuota')  {{-- Solo mostrar financiaciones con cuotas --}}
                                                        @php
                                                            // Calcular el monto que le corresponde al acreedor según su porcentaje
                                                            $montoAcreedorCuota = $item->cuota->monto * ($item->porcentaje / 100);
                                                            
                                                            // Actualizar los totales según el estado
                                                            if ($item->estado == 'pagado' || $item->estado == 'pagada') {
                                                                $montoPagadoTotal += $montoAcreedorCuota;
                                                                $montoTotalMes += $item->cuota->monto; // Total general incluye el monto completo
                                                            } elseif ($item->estado == 'parcial') {
                                                                $montoPagadoTotal += $item->monto_pagado_acreedor;
                                                                $montoPendienteTotal += $item->monto_pendiente_acreedor;
                                                                $montoTotalMes += $item->cuota->monto;
                                                            } else {
                                                                // Pendiente
                                                                $montoPendienteTotal += $montoAcreedorCuota;
                                                                $montoTotalMes += $item->cuota->monto;
                                                            }
                                                            
                                                            // Verificar si hay cuotas pagadas
                                                            if ($item->estado == 'pagado' || $item->estado == 'pagada') {
                                                                $estadoGeneral = 'pagado';
                                                            } elseif ($item->estado == 'parcial' && $estadoGeneral != 'pagado') {
                                                                $estadoGeneral = 'parcial';
                                                            }
                                                        @endphp
                                                        
                                                        <tr class="table-row-fixed table-row-striped">
                                                            <td style="width: 40%" class="align-middle">
                                                                <div>{{ $item->nombre_comprador }}</div>
                                                            </td>
                                                            <td style="width: 15%" class="text-center align-middle">
                                                                <span class="porcentaje-badge">{{ $item->porcentaje }}%</span>
                                                            </td>
                                                            <td style="width: 20%" class="text-center align-middle">
                                                                <span class="badge-estado-{{ $item->estado }}">
                                                                    {{ strtoupper($item->estado) }}
                                                                </span>
                                                            </td>
                                                            <td style="width: 25%" class="monto-valor align-middle">
                                                                <div class="monto-cell">
                                                                    @if($item->estado == 'pagada' || $item->estado == 'pagado')
                                                                        <span class="monto-pagado">U$D {{ number_format($item->cuota->monto * ($item->porcentaje / 100), 2) }}</span>
                                                                        <span class="monto-total totales-text" style="display: none;">Total: U$D {{ number_format($item->cuota->monto, 2) }}</span>
                                                                    @elseif($item->estado == 'parcial')
                                                                        <span class="monto-parcial">U$D {{ number_format($item->monto_pagado_acreedor, 2) }}</span>
                                                                        <span class="monto-pendiente pendientes-text" style="display: none;">Pend: U$D {{ number_format($item->monto_pendiente_acreedor, 2) }}</span>
                                                                    @else
                                                                        <span class="monto-pendiente">U$D 0.00</span>
                                                                        <span class="monto-total totales-text" style="display: none;">Total: U$D {{ number_format($item->cuota->monto * ($item->porcentaje / 100), 2) }}</span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <td colspan="2" class="align-middle"><strong class="fs-5">Total Mes:</strong></td>
                                                    <td class="text-center align-middle" id="estadoTotalMes-{{ $acreedor->id }}">
                                                        {{-- Etiqueta de estado eliminada --}}
                                                    </td>
                                                    <td class="text-end fs-5 fw-bold align-middle" id="montoTotalMes-{{ $acreedor->id }}">
                                                        @if($estadoGeneral == 'pagado')
                                                            <span class="monto-pagado">U$D {{ number_format($montoPagadoTotal, 2) }}</span>
                                                            <div class="monto-total totales-text" style="display: none;">Total: U$D {{ number_format($montoTotalMes, 2) }}</div>
                                                        @elseif($estadoGeneral == 'parcial')
                                                            <span class="monto-parcial">U$D {{ number_format($montoPagadoTotal, 2) }}</span>
                                                            <div class="monto-pendiente pendientes-text" style="display: none;">Pend: U$D {{ number_format($montoPendienteTotal, 2) }}</div>
                                                        @else
                                                            <span class="monto-pendiente">U$D 0.00</span>
                                                            <div class="monto-total totales-text" style="display: none;">Total: U$D {{ number_format($montoPendienteTotal, 2) }}</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                    
                    <!-- Contenido de la pestaña para nuevo acreedor -->
                    <div class="tab-pane fade" id="content-new" role="tabpanel" aria-labelledby="tab-new">
                        <!-- Formulario para agregar nuevo acreedor -->
                        <div class="p-4">
                            <h4 class="mb-3">Agregar Nuevo Acreedor</h4>
                            <form action="{{ url('/acreedores') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre del Acreedor</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción (opcional)</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Guardar Acreedor</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Panel lateral (25%) -->
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    Resumen
                </h5>
            </div>
            <div class="card-body">

                
                <!-- Mantener solo la definición de $otrosAcreedores que se usa más adelante -->
                @php
                    $otrosAcreedores = $acreedores->where('id', '!=', 1);
                @endphp
                
                <h5 class="mb-3">Saldos Pendientes</h5>
                <ul class="list-group">
                    @php
                        $calculosController = new \App\Http\Controllers\AcreedorCalculosController();
                        $totalSaldos = 0;
                    @endphp
                    
                    @foreach($otrosAcreedores as $acreedor)
                        @php
                            $resumen = $calculosController->obtenerResumenAcreedor($acreedor->id);
                            $saldo = $resumen['saldo'];
                            $totalSaldos += $saldo;
                        @endphp
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $acreedor->nombre }}
                            <span class="{{ $saldo < 0 ? 'text-success' : '' }}">
                                U$D {{ number_format(abs($saldo), 2) }}
                                @if($saldo < 0) 
                                    <small>(a favor)</small>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
                
                <div class="alert alert-success mt-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Total Liquidaciones:</strong>
                        <strong class="{{ $totalSaldos < 0 ? 'text-success' : '' }}">
                            U$D {{ number_format(abs($totalSaldos), 2) }}
                            @if($totalSaldos < 0) 
                                <small>(a favor)</small>
                            @endif
                        </strong>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#createAcreedorModal">
                        <i class="fas fa-plus-circle me-1"></i> Agregar Acreedor
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- COMPONENTE: Vista Panorámica Semestral -->
<div class="row mt-4">
    <div class="col-12">
        @php
            $panoramicaController = new \App\Http\Controllers\PanoramicaSemestralAcreedoresController();
            $panoramicaData = $panoramicaController->prepararDatos();
            $acreedoresSemestral = $panoramicaData['acreedores'];
            $months = $panoramicaData['months'];
        @endphp
        
        @include('components.panoramica-semestral-acreedores', [
            'acreedores' => $acreedoresSemestral,
            'months' => $months
        ])
    </div>
</div>

<!-- Modal para crear nuevo acreedor -->
<div class="modal fade" id="createAcreedorModal" tabindex="-1" aria-labelledby="createAcreedorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('gestion.acreedores.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createAcreedorModalLabel">Crear Nuevo Acreedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Acreedor</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/acreedores_index.js') }}"></script>
@endsection 