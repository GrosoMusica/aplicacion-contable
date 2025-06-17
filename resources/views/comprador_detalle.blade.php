@extends('layouts.app')

@section('title', 'Detalle del Comprador - ' . $comprador->nombre)

@section('styles')
    <!-- Incluimos el CSS externo -->
    <link href="{{ asset('css/comprador_detalle.css') }}" rel="stylesheet">

@endsection

@section('content')
    <div class="container mt-5">
        <!-- Botón Volver -->
        <div class="mb-4">
            <a href="{{ url()->previous() }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="row">
            <!-- Datos del Comprador con solo iconos -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white fw-bold">
                        Datos del Comprador
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="field-value">
                                        <i class="fas fa-user icon-field"></i>
                                        {{ $comprador->nombre }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="field-value">
                                        <i class="fas fa-id-card icon-field"></i>
                                        {{ $comprador->dni }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="field-value">
                                        @php
                                            $contrato = \App\Models\Contrato::where('id_comprador', $comprador->id)->first();
                                        @endphp
                                        <i class="fas fa-landmark icon-field cursor-pointer" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#cuentaRentasModal"></i>
                                        
                                        <span id="cuenta-rentas-valor">{{ $contrato ? $contrato->cuenta_rentas : 'No asignado' }}</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="field-value">
                                        <i class="fas fa-scroll icon-field"></i>
                                        <button type="button" class="contract-button text-primary" data-bs-toggle="modal" data-bs-target="#contractModal">
                                            Contrato
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="field-value">
                                        <i class="fas fa-map-marker-alt icon-field"></i>
                                        {{ $comprador->direccion }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="field-value">
                                        <i class="fas fa-phone icon-field"></i>
                                        {{ $comprador->telefono }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="field-value">
                                        <i class="fas fa-envelope icon-field"></i>
                                        {{ $comprador->email }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance con lógica corregida para excluir pagos excedentes -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white fw-bold">
                        Balance
                    </div>
                    <div class="card-body">
                        @php
                            // 1. Buscar la financiación por comprador_id
                            $financiacion = \App\Models\Financiacion::where('comprador_id', $comprador->id)->first();
                            $montoTotalFinanciacion = $financiacion ? $financiacion->monto_a_financiar : 0;
                            
                            // 2. Calcular el monto abonado siguiendo el flujo de datos exacto
                            $pagosRealizados = 0;
                            
                            if ($financiacion) {
                                // a. Obtener los IDs de todas las cuotas que pertenecen a esta financiación
                                $cuotasIds = \App\Models\Cuota::where('financiacion_id', $financiacion->id)
                                                             ->pluck('id')
                                                             ->toArray();
                                
                                // b. Sumar todos los montos de pagos asociados a esas cuotas
                                //    EXCLUYENDO aquellos marcados como pagos excedentes
                                if (!empty($cuotasIds)) {
                                    $pagosRealizados = \App\Models\Pago::whereIn('cuota_id', $cuotasIds)
                                                                      ->where(function($query) {
                                                                          $query->where('es_pago_excedente', '!=', 1)
                                                                                ->orWhereNull('es_pago_excedente');
                                                                      })
                                                                      ->sum('monto_usd');
                                }
                            }
                            
                            // 3. Cálculo de información de cuotas para la barra de progreso
                            $totalCuotas = $cuotas->count();
                            $cuotasPagadas = $cuotas->where('estado', 'pagada')->count();
                            $cuotasPendientes = $totalCuotas - $cuotasPagadas;
                            $porcentajePagado = ($totalCuotas > 0) ? ($cuotasPagadas / $totalCuotas) * 100 : 0;
                        @endphp
                        
                        <p class="mb-3"><strong>Abonado Hasta la Fecha:</strong> U$D {{ number_format($pagosRealizados, 2) }} / U$D {{ number_format($montoTotalFinanciacion, 2) }}</p>
                        
                        <hr>
                        
                        <div class="mt-3">
                            <p class="mb-1"><strong>Resumen de Cuotas</strong></p>
                            <div class="d-flex justify-content-between small mb-2">
                                <span>Total: {{ $totalCuotas }}</span>
                                <span>Pagadas: {{ $cuotasPagadas }}</span>
                                <span>Pendientes: {{ $cuotasPendientes }}</span>
                            </div>
                            
                            <!-- Barra de progreso -->
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: {{ $porcentajePagado }}%;" 
                                     aria-valuenow="{{ $porcentajePagado }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    {{ round($porcentajePagado) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del Lote -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white fw-bold">
                        Información del Lote
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Manzana:</strong> {{ $comprador->lote->manzana }}</p>
                                <p><strong>Lote:</strong> {{ $comprador->lote->lote }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Loteo:</strong> {{ $comprador->lote->loteo }}</p>
                                <p><span style="font-size: 1.2rem; font-style: italic;">{{ $comprador->lote->mts_cuadrados }} mt<sup>2</sup></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cuota Actual con modificaciones -->
            <div class="col-md-6">
                <div class="card mb-4">
                    @php
                        // Fechas básicas para comparación
                        $hoy = \Carbon\Carbon::now();
                        $inicioMes = \Carbon\Carbon::now()->startOfMonth();
                        $finMes = \Carbon\Carbon::now()->endOfMonth();
                        
                        // Encontrar ÚNICAMENTE la cuota correspondiente al mes actual
                        $cuotaActual = $cuotas->filter(function($cuota) use ($inicioMes, $finMes) {
                            return $cuota->fecha_de_vencimiento >= $inicioMes && 
                                   $cuota->fecha_de_vencimiento <= $finMes;
                        })->first();
                        
                        // Definir la clase de color según el estado
                        $headerColorClass = 'bg-secondary'; // Color predeterminado para "fuera de tiempo"
                        
                        if ($cuotaActual) {
                            if ($cuotaActual->estado === 'pendiente') {
                                $headerColorClass = 'bg-danger';
                            } elseif ($cuotaActual->estado === 'parcial') {
                                $headerColorClass = 'bg-warning text-dark';
                            } elseif ($cuotaActual->estado === 'pagada') {
                                $headerColorClass = 'bg-success';
                            }
                        }
                    @endphp

                    <div class="card-header {{ $headerColorClass }} text-white fw-bold" id="cuotaActualHeader">
                        <div class="d-flex justify-content-between align-items-center">
                            @if($cuotaActual)
                                <span>Cuota del Mes #{{ $cuotaActual->numero_de_cuota }} ({{ $hoy->format('F Y') }})</span>
                            @else
                                <span>Sin cuotas este mes</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if($cuotaActual)
                            <div class="d-flex justify-content-between mb-3">
                                <div>
                                    <p class="mb-1">Monto: U$D {{ number_format($cuotaActual->monto, 2) }}</p>
                                    <p>Vencimiento: {{ $cuotaActual->fecha_de_vencimiento->format('d-m-Y') }}</p>
                                    
                                    @if($cuotaActual->estado === 'pagada')
                                        <div class="payment-status status-paid">
                                            <i class="fas fa-check-circle me-1"></i> PAGADA
                                        </div>
                                    @elseif($cuotaActual->estado === 'parcial')
                                        @php
                                            $totalPagado = $cuotaActual->pagos->sum('monto_usd');
                                            $saldoPendiente = $cuotaActual->monto - $totalPagado;
                                            $porcentajePagado = ($totalPagado / $cuotaActual->monto) * 100;
                                        @endphp
                                        <div class="payment-status status-partial">
                                            <i class="fas fa-exclamation-circle me-1"></i>
                                            PAGO PARCIAL ({{ number_format($porcentajePagado, 0) }}%)
                                        </div>
                                        <p class="text-warning mt-2">
                                            Pendiente: U$D {{ number_format($saldoPendiente, 2) }}
                                        </p>
                                    @else
                                        <div class="payment-status status-pending">
                                            <i class="fas fa-times-circle me-1"></i> PENDIENTE
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <!-- Enlace a la página de pagos con fondo oscuro -->
                                    <a href="{{ route('pagos.index', ['comprador_id' => $comprador->id]) }}" 
                                       class="btn btn-dark">
                                        <i class="fas fa-list-alt me-1"></i> Ver Cuotas
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="text-center my-3">
                                <p class="text-secondary"><i class="fas fa-calendar-times fa-2x mb-2"></i></p>
                                <p>No hay cuotas programadas para este mes.</p>
                                <p class="small text-muted">Verifique el cronograma de pagos para más detalles.</p>
                                
                                <!-- También cambiar el botón aquí cuando no hay cuotas -->
                                <a href="{{ route('pagos.index', ['comprador_id' => $comprador->id]) }}" 
                                   class="btn btn-dark mt-2">
                                    <i class="fas fa-list-alt me-1"></i> Ver Cuotas
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Acreedores -->
            <div class="col-md-12 mb-4">
                @if(Auth::check() && Auth::user()->role == 'admin')
                    <x-acreedores :acreedores="$acreedores" :comprador="$comprador" />
                @endif
            </div>
        </div>
    </div>

    <!-- Modal para gestionar contrato -->
    <div class="modal fade" id="contractModal" tabindex="-1" aria-labelledby="contractModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contractModalLabel">Gestionar Contrato</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @php
                        $contrato = \App\Models\Contrato::where('id_comprador', $comprador->id)->first();
                    @endphp

                    @if($contrato && $contrato->ruta_contrato)
                        <div class="mb-3">
                            <i class="fas fa-file-pdf fa-3x text-danger"></i>
                            <p class="mt-2">Contrato actual</p>
                        </div>
                        <div class="contract-actions">
                            <a href="{{ route('contratos.ver', ['id' => $contrato->id]) }}" class="btn btn-primary" target="_blank">
                                <i class="fas fa-eye"></i> Ver Contrato
                            </a>
                            <form action="{{ route('contratos.actualizar', ['id' => $contrato->id]) }}" method="POST" enctype="multipart/form-data" style="display: inline;">
                                @csrf
                                @method('PUT')
                                <div class="input-group">
                                    <input type="file" class="form-control" name="contrato" accept=".pdf" required>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-upload"></i> Actualizar
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="mb-3">
                            <i class="fas fa-file-upload fa-3x text-primary"></i>
                            <p class="mt-2">No hay contrato cargado</p>
                        </div>
                        <form action="{{ route('contratos.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="id_comprador" value="{{ $comprador->id }}">
                            <div class="input-group">
                                <input type="file" class="form-control" name="contrato" accept=".pdf" required>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-upload"></i> Subir Contrato
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar Cuenta de Rentas -->
    <div class="modal fade" id="cuentaRentasModal" tabindex="-1" aria-labelledby="cuentaRentasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cuentaRentasModalLabel">Editar N° Cuenta de Rentas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formCuentaRentas" action="{{ $contrato ? route('contratos.updateCuentaRentas', ['id' => $contrato->id]) : route('contratos.store') }}" method="POST">
                    @csrf
                    @if($contrato)
                        @method('PUT')
                    @endif
                    <input type="hidden" name="id_comprador" value="{{ $comprador->id }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="cuenta_rentas" class="form-label">N° de Cuenta</label>
                            <input type="number" class="form-control" id="cuenta_rentas" name="cuenta_rentas" 
                                   value="{{ $contrato ? $contrato->cuenta_rentas : '' }}" required>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mantener funcionalidad para resaltar cuota pagada
        @if(session('cuota_pagada_id'))
            setTimeout(function() {
                // Buscar la cuota en la grilla
                const cuotaCards = document.querySelectorAll('.cuota-card');
                for (let i = 0; i < cuotaCards.length; i++) {
                    if (cuotaCards[i].querySelector('[data-cuota-id="{{ session("cuota_pagada_id") }}"]')) {
                        cuotaCards[i].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        cuotaCards[i].classList.add('highlight-cuota');
                        break;
                    }
                }
            }, 500);
        @endif
    });
</script>
@endsection 